# 动态规划
## 问题由来
最近做一个活动,给用户发红包,需要给用户按照投资金额来激活红包.
举个例子:用户有5,3,3,2元的红包,如果用户用户投资了600元需要激活两个3块的红包.即激活红包的总金额的100倍不能超过用户投资金额.
## 问题抽象
求一个正整数序列L(a0,a1,...,an)和不超过某一个正整数(M)的最大值.
## 分析
直接将序列排序,无论从大开始选,还是从小开始选,都不能保证得到最优解.采用动态规划来求解:
- 使用L的前i个元素不超过m的最优解记为dp(i,m)
- 假设已经达到最优解,即使用L中所有元素,不超过M的结果是dp(n,M)为最优解
- 反推最优解来源:即分析第n个该不该加进来的问题
  - dp(n-1,M) 不该加进来,加进来就超过M了
  - dp(n-1,M-an) + an 该加进来,则解为减去最后一个值的最优解与最后一个值的和
  - dp(n,M) = max(dp(n-1,M) , dp(n-1,M-an) + an) 这个就是最优解的计算递推公式
- 写代码
  - 直接递归 很明显直接递归会重复计算之前已经计算的结果
  - 使用一个二维数组保存计算的过程 从头开始算 算到最后 
  - 使用一个滚动数组
## 代码
- 递归
```php
<?php
function getMax($array,$n,$m){
    if($m <=0 || $n < 0){
        return 0;
    }
    if($n == 0){
        if($m >= $array[0]){
            return $array[0];
        }
        return 0;
    } 

    $notUse = getMax($array,$n-1,$m);
    $use = getMax($array,$n-1,$m-$array[$n-1]) +$array[$n-1];
    if($use > $m){
        return 0;
    }
    return max($notUse,$use);

}

$array=[5,3,3,2];
$n=sizeof($array);
$m=6;
echo getMax($array,$n,$m);
```
- 利用一个二维数组保存计算出来的值,然后根据结果反推哪些数用到了
```php
<?php
function getMax($array, $n, $m)
{
    for ($i = 0; $i <$n; $i ++) {
        for ($j=0; $j<=$m; $j++) {
            if ($j==0) {
                $retMatrix[$i][$j]=0;
                continue;
            }
            if ($j>=$array[$i]) {
                $retMatrix[$i][$j] = max(
                    intval($retMatrix[$i-1][$j]),
                    intval($retMatrix[$i-1][$j-$array[$i]])+ $array[$i]
                );
            } else {
                $retMatrix[$i][$j] = $retMatrix[$i-1][$j];
            }
        }
    }
    return $retMatrix;
}

function getTrace($array, $retMatrix, $n, $m)
{
    for ($i = $n-1; $i>0; $i --) {
        if ($retMatrix[$i][$m] == $retMatrix[$i-1][$m]) {
            // the i-th of the array is not used
            $ret[$i]=0;
        } else {
            // the i-th of the array is used
            $ret[$i]=1;
            $m -= $array[$i];
        }
    }
    $ret[0] = $retMatrix[0][$m] > 0 ? 1:0;
    return $ret;
}

$array=[5,3,3,2];
$n=sizeof($array);
$m=6;
$retMatrix = getMax($array, $n, $m);
var_dump($retMatrix[$n-1][$m]);//int(6)
var_dump(getTrace($array, $retMatrix, $n, $m));//array(0,1,1,0)
```
- 使用滚动数组, dp(n,M) = max(dp(n-1,M) , dp(n-1,M-an) + an), 只与n-1的两个数有关,可以用一个数组dp(M)来保存使用i个数的状态.即dp(M)始终保存使用i个数的最好结果.所以判断是否使用第i个数即判断第i个数的最好结果dp(M),可以表示上一个i-1的最好结果dp(M)即不使用i , 和使用i+1,dp(M-ai) + ai的较大者
```php
<?php

function getMax($array, $n, $m)
{
    for ($i=0; $i<$n; $i++) {
        for ($j=$m; $j>=$array[$i]; $j--) {
            $ret[$j] = max($ret[$j], $ret[$j-$array[$i]]+$array[$i]);
        }
    }
    return $ret;
}

$array=[5,3,3,2];
$n=sizeof($array);
$m=6;
$ret = getMax($array, $n, $m);
var_dump($ret);
```
 
