# PPS

> PHP Project Scaffold – minimal and ready to code

![License](https://img.shields.io/github/license/hizpark/pps?style=flat-square)
![Latest Version](https://img.shields.io/packagist/v/hizpark/pps?style=flat-square)
![PHP Version](https://img.shields.io/badge/php-8.2--8.4-blue?style=flat-square)
![Static Analysis](https://img.shields.io/badge/static_analysis-PHPStan-blue?style=flat-square)
![Tests](https://img.shields.io/badge/tests-PHPUnit-brightgreen?style=flat-square)
[![codecov](https://codecov.io/gh/hizpark/pps/branch/main/graph/badge.svg)](https://codecov.io/gh/hizpark/pps)
![CI](https://github.com/hizpark/pps/actions/workflows/ci.yml/badge.svg?style=flat-square)

A command-line tool that generates new PHP projects from pre-configured templates with essential development tools ready.

## 🚀 用法

你可以通过以下两种方式：

方法 1：通过 Composer 快速创建项目

```bash
composer create-project hizpark/pps new-project
```

方法 2：使用独立的 .phar 可执行文件

```bash
wget https://github.com/hizpark/pps/releases/latest/download/pps.phar
chmod +x pps.phar
./pps.phar init new-project
```

你也可以将 pps.phar 移动到 /usr/local/bin/pps 来实现全局使用：

```bash
sudo mv pps.phar /usr/local/bin/pps
pps init my-project
```

## 🧩 占位符替换

初始化项目后，您会在源文件中发现各种占位符字符串，例如：

- `pps.vendor`
- `pps.repo_name`
- `pps.repo_src_namespace`
- `pps.license_year`

完整占位符列表在 `.pps.placeholders.php` 文件中，您可以手动或使用脚本来搜索和替换它们。

### 🔍 搜索所有占位符

```bash
grep 'pps\.' -r .
```

### 🔄 替换示例

```bash
# Replace vendor name
find . -type f -exec sed -i 's/pps.vendor/hizpark/g' {} \;
```

## 🔍 静态分析

使用 PHPStan 工具进行静态分析，确保代码的质量和一致性：

```bash
composer stan
```

## 🎯 代码风格

使用 PHP-CS-Fixer 工具检查代码风格：

```bash
composer cs:chk
```

使用 PHP-CS-Fixer 工具自动修复代码风格问题：

```bash
composer cs:fix
```

## ✅ 单元测试

执行 PHPUnit 单元测试：

```bash
composer test
```

执行 PHPUnit 单元测试并生成代码覆盖率报告：

```bash
composer test:coverage
```

## 🤝 贡献指南

欢迎 Issue 与 PR，建议遵循以下流程：

1. Fork 仓库
2. 创建新分支进行开发
3. 提交 PR 前请确保测试通过、风格一致
4. 提交详细描述

## 📜 License

MIT License. See the [LICENSE](LICENSE) file for details.
