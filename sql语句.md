# 写的一些sql语句 记下来

### 按字段a分组之后 根据字段b的不同情况 统计字段c的和

统计用户在投资金中,两个产品的和
```sql
SELECT
    user_id,
    money,
    money_return,
    investing_money,
    sum( CASE WHEN type = 1 OR type = 2 OR type = 3 OR type = 7 THEN amount ELSE 0 END ) AS mm,
    sum( CASE WHEN type = 4 OR type = 5 OR type = 6 OR type = 8 THEN amount ELSE 0 END ) AS cfd 
FROM
    (
SELECT
    u.user_id AS user_id,
    p.id AS plan_id,
    p.type AS type,
    u.money,
    u.money_return,
    u.investing_money,
    r.amount 
FROM
    tp_coupon.stpc_user AS u
    JOIN mmplan.mmp_return AS r ON r.uid = u.user_id
    JOIN mmplan.mmp_project_record AS pr ON pr.id = r.pr_id
    JOIN mmplan.mmp_project AS prj ON prj.id = pr.prj_id
    JOIN mmplan.mmp_plan AS p ON p.id = prj.plan_id 
WHERE
    u.investing_money > 0 
    AND r.STATUS = 1 
    AND r.is_capital = 1 
    ) AS tmp 
GROUP BY
    user_id
```
使用case when ... then ... else ... end 来统计
