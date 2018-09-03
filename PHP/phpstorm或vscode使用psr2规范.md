- 安装composer
- 全局安装phpcs
```
composer global require squizlabs/php_codesniffer
```

### vscode直接插件搜索phpcs安装
### phpstorm
全局安装phpcs后，会在**C:\Users\{user name}\AppData\Roaming\Composer\vendor\bin**下生成一个`phpcs.bat`，后面会用到
- phpstorm -> setting
- languages & Frameworks->PHP->Code Sniffer点击Configuration右侧的按钮
- 找到刚才的phpcs.bat，点击`Validate`，确认
- Editor->Inspection->PHP
- 双击PHP Code Sniffer validation，点击Coding standard右侧的刷新按钮，然后选择psr2，确定

**参考链接**: [如何优雅地使用phpstorm?](https://laravel-china.org/topics/1692/how-to-use-phpstorm-gracefully)