# laravel-builder-batch
Laravel Model 批处理

```php
/**
 * $option['other']
 * $option['type']
 * $option['key'] 默认主键
 **/
simpleBatch(string $column, array $values = [], array $option = []):int
```



1. 无Other
```php
User::query()->simpleBatch('password', [1 => '123', 2 => '321']);
//DB::table('users')->simpleBatch('password', [1 => '123', 2 => '321']);
```

```mysql
update `users` set `users`.`password` = ( case `users`.`id` when 1 then '123' when 2 then '321' end ), `users`.`updated_at` = "2022-02-08 15:42:32" where `users`.`id` in (1, 2);
```
2. 有Other
```php
User::query()->simpleBatch('password', [1 => '123', 2 => '321'],['other'=>'8888']);
//DB::table('users')->simpleBatch('password', [1 => '123', 2 => '321'],['other'=>'8888']);
```

```mysql
update `users` set `users`.`password` = ( case `users`.`id` when 1 then '123' when 2 then '321' else '8888' end ), `users`.`updated_at` = "2022-02-08 15:45:11";
```

3. ['+', '-', '*', '/', '%']
```php
User::query()->simpleBatch('password', [1 => '123', 2 => '321'], ['type' => '+']);
// DB::table('users')->simpleBatch('password', [1 => '123', 2 => '321'], ['type' => '+']);
```
```mysql
update `users` set `users`.`password` = ( case `users`.`id` when 1 then `users`.`password` + 123 when 2 then `users`.`password` + 321 end ), `users`.`updated_at` = "2022-02-08 15:42:32" where `users`.`id` in (1, 2);
```
