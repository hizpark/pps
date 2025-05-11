# PHP 模板说明

本模板提供一个干净、可维护、符合现代开发规范的 PHP 项目起始结构，适用于构建各种类型的 PHP 应用或函数库。

## ✨ 模板特性

- **「标准化项目结构」**
  预设 PSR-4 命名空间与目录布局，内建 CI/CD 工作流，开箱即用

- **「内建质量工具链」**
  集成 PHPStan、PHP-CS-Fixer、PHPUnit，保障代码可读性与正确性

- **「开源级生产配置」**
  默认采用 MIT 协议，配置注重安全性与生产部署标准

- **「全量占位符支持」**
  支持项目名称、命名空间、版本号等动态变量，自动替换生成

- **「极速初始化体验」**
  借助 `pps` 脚手架，一键生成结构完整、即刻可用的新项目

## 📂 目录结构说明

```txt
php
├── .github/
│   └── workflows/
│       └── ci.yml               # GitHub Actions CI 配置
├── .editorconfig                # 编辑器格式统一规则
├── .gitattributes               # Git diff 行为与归档控制
├── .gitignore                   # 忽略不需要提交的文件
├── .php-cs-fixer.dist.php       # PHP-CS-Fixer 配置
├── .pps.placeholders.php        # 占位符定义文件，用于动态替换
├── composer.json                # 项目依赖与元信息
├── phpstan.neon.dist            # PHPStan 静态分析配置
├── phpunit.xml.dist             # PHPUnit 测试配置
├── LICENSE                      # MIT 开源协议
└── README.md                    # 模板生成后的项目说明文档
```

## 🚀 使用方式

### 手动方式

1. 克隆`pps`：
   ```bash
   git clone https://github.com/hizpark/pps.git
   ```

2. 拷贝模板目录：
   ```bash
   cp -r pps/templates/php new-project
   ```

### 使用 `pps` 脚手架（推荐）

1. 安装 [`pps`](https://github.com/hizpark/pps)：
   ```bash
   wget https://github.com/hizpark/pps/releases/latest/download/pps.phar
   chmod +x pps.phar
   ```

2. 初始化新项目：
   ```bash
   ./pps.phar init new-project --template=php
   ```

用法详情请参见：[pps-doc](https://github.com/hizpark/pps#readme)

## 🧩 常用占位符

模板支持以下动态占位符，会在项目初始化时自动替换为实际值：

| 占位符                    | 描述    |
|------------------------|-------|
| `pps.vendor`           | 项目厂商名 |
| `pps.repo_name`        | 项目名称  |
| `pps.repo_type`        | 项目类型  |
| `pps.repo_description` | 项目描述  |

完整定义见 `php/.pps.placeholders.php` 文件。

## 🛠️ 默认质量工具版本

| 工具名称         | 版本范围 | 功能说明      |
|--------------|------|-----------|
| PHPStan      | 2.x  | 静态分析与类型检查 |
| PHP-CS-Fixer | 3.x  | 代码格式化工具   |
| PHPUnit      | 11.x | 单元测试框架    |
