---
title: php扩展
date: 2019-01-11 09:21:17
tags:
- php
- php扩展
- php实现
categories:
- 源码分析
---

分析`php`执行流程,然后实现一个php扩展

<!-- more -->

### php执行流程分析--`cli`为例

`php-src`目录中的`sapi`目录下面提供了`php`的多种执行方式,以`cli`来看`php`执行流程.

为了简便起见,先不用分析不同平台条件编译和线程安全的代码.
```c
#ifdef PHP_CLI_WIN32_NO_CONSOLE
int WINAPI WinMain(HINSTANCE hInstance, HINSTANCE hPrevInstance, LPSTR lpCmdLine, int nShowCmd)
#else
int main(int argc, char *argv[])
#endif
{
#if defined(PHP_WIN32)
# ifdef PHP_CLI_WIN32_NO_CONSOLE
	int argc = __argc;
	char **argv = __argv;
# else
	int num_args;
	wchar_t **argv_wide;
	char **argv_save = argv;
	BOOL using_wide_argv = 0;
# endif
#endif

	int c;
	int exit_status = SUCCESS;
	int module_started = 0, sapi_started = 0;
	char *php_optarg = NULL;
	int php_optind = 1, use_extended_info = 0;
	char *ini_path_override = NULL;
	char *ini_entries = NULL;
	int ini_entries_len = 0;
	int ini_ignore = 0;
	sapi_module_struct *sapi_module = &cli_sapi_module;

	/*
	 * Do not move this initialization. It needs to happen before argv is used
	 * in any way.
	 */
	argv = save_ps_args(argc, argv);

	cli_sapi_module.additional_functions = additional_functions;

#if defined(PHP_WIN32) && defined(_DEBUG) && defined(PHP_WIN32_DEBUG_HEAP)
	{
		int tmp_flag;
		_CrtSetReportMode(_CRT_WARN, _CRTDBG_MODE_FILE);
		_CrtSetReportFile(_CRT_WARN, _CRTDBG_FILE_STDERR);
		_CrtSetReportMode(_CRT_ERROR, _CRTDBG_MODE_FILE);
		_CrtSetReportFile(_CRT_ERROR, _CRTDBG_FILE_STDERR);
		_CrtSetReportMode(_CRT_ASSERT, _CRTDBG_MODE_FILE);
		_CrtSetReportFile(_CRT_ASSERT, _CRTDBG_FILE_STDERR);
		tmp_flag = _CrtSetDbgFlag(_CRTDBG_REPORT_FLAG);
		tmp_flag |= _CRTDBG_DELAY_FREE_MEM_DF;
		tmp_flag |= _CRTDBG_LEAK_CHECK_DF;

		_CrtSetDbgFlag(tmp_flag);
	}
#endif

#ifdef HAVE_SIGNAL_H
#if defined(SIGPIPE) && defined(SIG_IGN)
	signal(SIGPIPE, SIG_IGN); /* ignore SIGPIPE in standalone mode so
								that sockets created via fsockopen()
								don't kill PHP if the remote site
								closes it.  in apache|apxs mode apache
								does that for us!  thies@thieso.net
								20000419 */
#endif
#endif


#ifdef ZTS
	tsrm_startup(1, 1, 0, NULL);
	(void)ts_resource(0);
	ZEND_TSRMLS_CACHE_UPDATE();
#endif

	zend_signal_startup();

#ifdef PHP_WIN32
	_fmode = _O_BINARY;			/*sets default for file streams to binary */
	setmode(_fileno(stdin), O_BINARY);		/* make the stdio mode be binary */
	setmode(_fileno(stdout), O_BINARY);		/* make the stdio mode be binary */
	setmode(_fileno(stderr), O_BINARY);		/* make the stdio mode be binary */
#endif

	while ((c = php_getopt(argc, argv, OPTIONS, &php_optarg, &php_optind, 0, 2))!=-1) {
		switch (c) {
			case 'c':
				if (ini_path_override) {
					free(ini_path_override);
				}
 				ini_path_override = strdup(php_optarg);
				break;
			case 'n':
				ini_ignore = 1;
				break;
			case 'd': {
				/* define ini entries on command line */
				int len = (int)strlen(php_optarg);
				char *val;

				if ((val = strchr(php_optarg, '='))) {
					val++;
					if (!isalnum(*val) && *val != '"' && *val != '\'' && *val != '\0') {
						ini_entries = realloc(ini_entries, ini_entries_len + len + sizeof("\"\"\n\0"));
						memcpy(ini_entries + ini_entries_len, php_optarg, (val - php_optarg));
						ini_entries_len += (int)(val - php_optarg);
						memcpy(ini_entries + ini_entries_len, "\"", 1);
						ini_entries_len++;
						memcpy(ini_entries + ini_entries_len, val, len - (val - php_optarg));
						ini_entries_len += len - (int)(val - php_optarg);
						memcpy(ini_entries + ini_entries_len, "\"\n\0", sizeof("\"\n\0"));
						ini_entries_len += sizeof("\n\0\"") - 2;
					} else {
						ini_entries = realloc(ini_entries, ini_entries_len + len + sizeof("\n\0"));
						memcpy(ini_entries + ini_entries_len, php_optarg, len);
						memcpy(ini_entries + ini_entries_len + len, "\n\0", sizeof("\n\0"));
						ini_entries_len += len + sizeof("\n\0") - 2;
					}
				} else {
					ini_entries = realloc(ini_entries, ini_entries_len + len + sizeof("=1\n\0"));
					memcpy(ini_entries + ini_entries_len, php_optarg, len);
					memcpy(ini_entries + ini_entries_len + len, "=1\n\0", sizeof("=1\n\0"));
					ini_entries_len += len + sizeof("=1\n\0") - 2;
				}
				break;
			}
#ifndef PHP_CLI_WIN32_NO_CONSOLE
			case 'S':
				sapi_module = &cli_server_sapi_module;
				cli_server_sapi_module.additional_functions = server_additional_functions;
				break;
#endif
			case 'h': /* help & quit */
			case '?':
				php_cli_usage(argv[0]);
				goto out;
			case 'i': case 'v': case 'm':
				sapi_module = &cli_sapi_module;
				goto exit_loop;
			case 'e': /* enable extended info output */
				use_extended_info = 1;
				break;
		}
	}
exit_loop:

	sapi_module->ini_defaults = sapi_cli_ini_defaults;
	sapi_module->php_ini_path_override = ini_path_override;
	sapi_module->phpinfo_as_text = 1;
	sapi_module->php_ini_ignore_cwd = 1;
	sapi_startup(sapi_module);
	sapi_started = 1;

	sapi_module->php_ini_ignore = ini_ignore;

	sapi_module->executable_location = argv[0];

	if (sapi_module == &cli_sapi_module) {
		if (ini_entries) {
			ini_entries = realloc(ini_entries, ini_entries_len + sizeof(HARDCODED_INI));
			memmove(ini_entries + sizeof(HARDCODED_INI) - 2, ini_entries, ini_entries_len + 1);
			memcpy(ini_entries, HARDCODED_INI, sizeof(HARDCODED_INI) - 2);
		} else {
			ini_entries = malloc(sizeof(HARDCODED_INI));
			memcpy(ini_entries, HARDCODED_INI, sizeof(HARDCODED_INI));
		}
		ini_entries_len += sizeof(HARDCODED_INI) - 2;
	}

	sapi_module->ini_entries = ini_entries;

	/* startup after we get the above ini override se we get things right */
	if (sapi_module->startup(sapi_module) == FAILURE) {
		/* there is no way to see if we must call zend_ini_deactivate()
		 * since we cannot check if EG(ini_directives) has been initialised
		 * because the executor's constructor does not set initialize it.
		 * Apart from that there seems no need for zend_ini_deactivate() yet.
		 * So we goto out_err.*/
		exit_status = 1;
		goto out;
	}
	module_started = 1;

#if defined(PHP_WIN32) && !defined(PHP_CLI_WIN32_NO_CONSOLE)
	php_win32_cp_cli_setup();
	orig_cp = (php_win32_cp_get_orig())->id;
	/* Ignore the delivered argv and argc, read from W API. This place
		might be too late though, but this is the earliest place ATW
		we can access the internal charset information from PHP. */
	argv_wide = CommandLineToArgvW(GetCommandLineW(), &num_args);
	PHP_WIN32_CP_W_TO_ANY_ARRAY(argv_wide, num_args, argv, argc)
	using_wide_argv = 1;

	SetConsoleCtrlHandler(php_cli_win32_ctrl_handler, TRUE);
#endif

	/* -e option */
	if (use_extended_info) {
		CG(compiler_options) |= ZEND_COMPILE_EXTENDED_INFO;
	}

	zend_first_try {
#ifndef PHP_CLI_WIN32_NO_CONSOLE
		if (sapi_module == &cli_sapi_module) {
#endif
			exit_status = do_cli(argc, argv);
#ifndef PHP_CLI_WIN32_NO_CONSOLE
		} else {
			exit_status = do_cli_server(argc, argv);
		}
#endif
	} zend_end_try();
out:
	if (ini_path_override) {
		free(ini_path_override);
	}
	if (ini_entries) {
		free(ini_entries);
	}
	if (module_started) {
		php_module_shutdown();
	}
	if (sapi_started) {
		sapi_shutdown();
	}
#ifdef ZTS
	tsrm_shutdown();
#endif

#if defined(PHP_WIN32) && !defined(PHP_CLI_WIN32_NO_CONSOLE)
	(void)php_win32_cp_cli_restore();

	if (using_wide_argv) {
		PHP_WIN32_CP_FREE_ARRAY(argv, argc);
		LocalFree(argv_wide);
	}
	argv = argv_save;
#endif
	/*
	 * Do not move this de-initialization. It needs to happen right before
	 * exiting.
	 */
	cleanup_ps_args(argv);
	exit(exit_status);
}
/* }}} */

```

- `sapi_module_struct`:

`sapi_module_struct`类似于一个接口,所有的启动方式中,都需要实现这个结构体的内容.

```c
struct _sapi_module_struct {
	char *name;
	char *pretty_name;

	int (*startup)(struct _sapi_module_struct *sapi_module);
	int (*shutdown)(struct _sapi_module_struct *sapi_module);

	int (*activate)(void);
	int (*deactivate)(void);

	size_t (*ub_write)(const char *str, size_t str_length);
	void (*flush)(void *server_context);
	zend_stat_t *(*get_stat)(void);
	char *(*getenv)(char *name, size_t name_len);

	void (*sapi_error)(int type, const char *error_msg, ...) ZEND_ATTRIBUTE_FORMAT(printf, 2, 3);

	int (*header_handler)(sapi_header_struct *sapi_header, sapi_header_op_enum op, sapi_headers_struct *sapi_headers);
	int (*send_headers)(sapi_headers_struct *sapi_headers);
	void (*send_header)(sapi_header_struct *sapi_header, void *server_context);

	size_t (*read_post)(char *buffer, size_t count_bytes);
	char *(*read_cookies)(void);

	void (*register_server_variables)(zval *track_vars_array);
	void (*log_message)(char *message, int syslog_type_int);
	double (*get_request_time)(void);
	void (*terminate_process)(void);

	char *php_ini_path_override;

	void (*default_post_reader)(void);
	void (*treat_data)(int arg, char *str, zval *destArray);
	char *executable_location;

	int php_ini_ignore;
	int php_ini_ignore_cwd; /* don't look for php.ini in the current directory */

	int (*get_fd)(int *fd);

	int (*force_http_10)(void);

	int (*get_target_uid)(uid_t *);
	int (*get_target_gid)(gid_t *);

	unsigned int (*input_filter)(int arg, char *var, char **val, size_t val_len, size_t *new_val_len);

	void (*ini_defaults)(HashTable *configuration_hash);
	int phpinfo_as_text;

	char *ini_entries;
	const zend_function_entry *additional_functions;
	unsigned int (*input_filter_init)(void);
};


static sapi_module_struct cli_sapi_module = {
	"cli",							/* name */
	"Command Line Interface",    	/* pretty name */

	php_cli_startup,				/* startup */
	php_module_shutdown_wrapper,	/* shutdown */

	NULL,							/* activate */
	sapi_cli_deactivate,			/* deactivate */

	sapi_cli_ub_write,		    	/* unbuffered write */
	sapi_cli_flush,				    /* flush */
	NULL,							/* get uid */
	NULL,							/* getenv */

	php_error,						/* error handler */

	sapi_cli_header_handler,		/* header handler */
	sapi_cli_send_headers,			/* send headers handler */
	sapi_cli_send_header,			/* send header handler */

	NULL,				            /* read POST data */
	sapi_cli_read_cookies,          /* read Cookies */

	sapi_cli_register_variables,	/* register server variables */
	sapi_cli_log_message,			/* Log message */
	NULL,							/* Get request time */
	NULL,							/* Child terminate */

	STANDARD_SAPI_MODULE_PROPERTIES
};

#define STANDARD_SAPI_MODULE_PROPERTIES \
	NULL, /* php_ini_path_override   */ \
	NULL, /* default_post_reader     */ \
	NULL, /* treat_data              */ \
	NULL, /* executable_location     */ \
	0,    /* php_ini_ignore          */ \
	0,    /* php_ini_ignore_cwd      */ \
	NULL, /* get_fd                  */ \
	NULL, /* force_http_10           */ \
	NULL, /* get_target_uid          */ \
	NULL, /* get_target_gid          */ \
	NULL, /* input_filter            */ \
	NULL, /* ini_defaults            */ \
	0,    /* phpinfo_as_text;        */ \
	NULL, /* ini_entries;            */ \
	NULL, /* additional_functions    */ \
	NULL  /* input_filter_init       */

#endif /* SAPI_H */

/* }}} */

```

- `zend_module_entry`

```c
struct _zend_module_entry {
	unsigned short size;
	unsigned int zend_api;
	unsigned char zend_debug;
	unsigned char zts;
	const struct _zend_ini_entry *ini_entry;
	const struct _zend_module_dep *deps;
	const char *name;
	const struct _zend_function_entry *functions;
	int (*module_startup_func)(INIT_FUNC_ARGS);
	int (*module_shutdown_func)(SHUTDOWN_FUNC_ARGS);
	int (*request_startup_func)(INIT_FUNC_ARGS);
	int (*request_shutdown_func)(SHUTDOWN_FUNC_ARGS);
	void (*info_func)(ZEND_MODULE_INFO_FUNC_ARGS);
	const char *version;
	size_t globals_size;
#ifdef ZTS
	ts_rsrc_id* globals_id_ptr;
#else
	void* globals_ptr;
#endif
	void (*globals_ctor)(void *global);
	void (*globals_dtor)(void *global);
	int (*post_deactivate_func)(void);
	int module_started;
	unsigned char type;
	void *handle;
	int module_number;
	const char *build_id;
};

typedef struct _zend_ini_entry_def {
	const char *name;
	ZEND_INI_MH((*on_modify));
	void *mh_arg1;
	void *mh_arg2;
	void *mh_arg3;
	const char *value;
	void (*displayer)(zend_ini_entry *ini_entry, int type);
	int modifiable;

	uint name_length;
	uint value_length;
} zend_ini_entry_def;

struct _zend_ini_entry {
	zend_string *name;
	ZEND_INI_MH((*on_modify));
	void *mh_arg1;
	void *mh_arg2;
	void *mh_arg3;
	zend_string *value;
	zend_string *orig_value;
	void (*displayer)(zend_ini_entry *ini_entry, int type);
	int modifiable;

	int orig_modifiable;
	int modified;
	int module_number;
};
```
- 首先是定义了一些变量(28-37)

- `sapi_startup()`:`sapi_started = 1`





- `sapi_module->startup()` :`module_started = 1`

- `do_cli()`

- `php_module_shutdown()`

- `sapi_shutdown()`


### php扩展试验

- 进入源码的`ext`目录,试试help,会有提示.

```
sawyer@thinkpad:/usr/local/src/php-7.1.20/ext$ ./ext_skel --extname=mytest
Creating directory mytest
Creating basic files: config.m4 config.w32 .gitignore mytest.c php_mytest.h CREDITS EXPERIMENTAL tests/001.phpt mytest.php [done].

To use your new extension, you will have to execute the following steps:

1.  $ cd ..
2.  $ vi ext/mytest/config.m4
3.  $ ./buildconf
4.  $ ./configure --[with|enable]-mytest
5.  $ make
6.  $ ./sapi/cli/php -f ext/mytest/mytest.php
7.  $ vi ext/mytest/mytest.c
8.  $ make

Repeat steps 3-6 until you are satisfied with ext/mytest/config.m4 and
step 6 confirms that your module is compiled into PHP. Then, start writing
code and repeat the last two steps as often as necessary.

```
    1. 略过
    2. 需要修改`config.m4`,里面写了,不修改是没有效果的.这里解释了`enable`和`with`的区别.去掉`with`前面的`dnl`.
```
dnl If your extension references something external, use with:

dnl PHP_ARG_WITH(mytest, for mytest support,
dnl Make sure that the comment is aligned:
dnl [  --with-mytest             Include mytest support])

dnl Otherwise use enable:

dnl PHP_ARG_ENABLE(mytest, whether to enable mytest support,
dnl Make sure that the comment is aligned:
dnl [  --enable-mytest           Enable mytest support])

```
    3. 给了一个提示.所以加了`--force`
```
You should not run buildconf in a release package.
use buildconf --force to override this check.
``` 
    4. 成功提示 `Thank you for using PHP` 
    5. make的时候放弃了...然后可以直接用安装`php`扩展的方式进行安装
        - cd mytest
        - phpize
        - ./configure --with-php-config=/usr/bin/php7/bin/php-config
        - sudo make && sudo make install
        - 修改`php.ini`发现有了这个`php`扩展
    6. 开始写代码了...
    7. 补充:第五步放弃的,是静态编译扩展到php里面.这个与使用`with`和`enable`没有必然的关系
