<?php

declare(strict_types=1);

namespace App\Command;

use FilesystemIterator;
use Hizpark\DirectoryTree\DirectoryTreeViewer;
use Hizpark\ZipMover\ZipMover;
use InvalidArgumentException;
use Phar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

class InitCommand extends Command
{
    public const MODE_LOCAL        = 'local';
    public const MODE_REMOTE       = 'remote';
    private const DEFAULT_TEMPLATE = 'php';

    private string $mode;

    private string $projectDir;

    private string $tmpWorkDir;

    /**
     * Scaffold constructor.
     *
     * @param ZipMover            $zipMover   The service to handle zipping and moving files.
     * @param DirectoryTreeViewer $treeViewer The service to view directory tree.
     * @param Filesystem          $filesystem The service for filesystem operations.
     */
    public function __construct(
        private readonly ZipMover $zipMover = new ZipMover(),
        private readonly DirectoryTreeViewer $treeViewer = new DirectoryTreeViewer(),
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
        $this->mode = getenv('MODE') ?: self::MODE_LOCAL;
        $this->prepareTmpWorkDir();
    }

    /**
     * Configures the command with arguments, options, and description.
     */
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initializing a new PHP project')
            ->addArgument('name', InputArgument::REQUIRED, 'Project Name')
            ->addOption('template', 't', InputOption::VALUE_REQUIRED, 'Template used', self::DEFAULT_TEMPLATE)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwriting of existing project');
    }

    /**
     * Executes the command.
     * This method will handle the overall project initialization process.
     *
     * @param InputInterface  $input  Input interface to fetch command arguments and options.
     * @param OutputInterface $output Output interface to send command results.
     *
     * @return int Command exit status.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $name = $this->stringify($input->getArgument('name'));
            $this->validateProjectName($name);

            $this->projectDir = $this->getProjectDir($name);

            $template = $this->stringify($input->getOption('template')) ?: self::DEFAULT_TEMPLATE;
            $force    = (bool)$input->getOption('force');

            $io->writeln('');
            $io->writeln("🚀 To initialize project from template [$template]:" . PHP_EOL);
            $io->writeln($this->treeViewer->render($this->getOriginTemplatePath($template)));

            $this->checkoutTemplateFiles($template);
            $io->writeln('✅ Template files packaging is complete and the compressed package is ready.' . PHP_EOL);

            $this->ensureProjectDirEmpty($force);
            $io->writeln('✅ The project directory has been checked and confirmed to be empty.' . PHP_EOL);

            $this->deploy();
            $io->writeln('✅ The compressed package has been decompressed and the files have been deployed.' . PHP_EOL);

            $this->cleanup();
            $io->writeln('✅ Temporary resources released.' . PHP_EOL);

            $io->writeln("✅ All done! Your [$name] project is ready." . PHP_EOL);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->writeln($e->getMessage());

            $io->writeln('Stack Trace:');
            $io->writeln($e->getTraceAsString());

            return Command::FAILURE;
        } finally {
            $this->cleanup();
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateProjectName(string $name): void
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Project name cannot be empty');
        }

        if (strlen($name) > 64) {
            throw new InvalidArgumentException('Project name cannot exceed 64 characters');
        }

        // 定义所有不允许的字符（比单纯允许的字符更严格）
        if (preg_match('/[^a-zA-Z0-9_-]/', $name, $matches)) {
            $illegalChar = $matches[0];

            throw new InvalidArgumentException(
                sprintf('Project name contains illegal character "%s"', $illegalChar),
            );
        }

        // 额外格式要求（开头/结尾规则）
        if (!preg_match('/^[a-zA-Z](.*[a-zA-Z0-9])?$/', $name)) {
            throw new InvalidArgumentException(
                'Must start with a letter and cannot end with underscore/hyphen',
            );
        }
    }

    /**
     * Deploys the project by moving and extracting files.
     */
    private function deploy(): void
    {
        $this->projectDirEmptyOrFail();
        $this->zipMover->compress($this->tmpWorkDir);
        $this->zipMover->extract($this->projectDir);
        $this->zipMover->clean();
    }

    /**
     * Cleans up temporary files and directories.
     */
    private function cleanup(): void
    {
        $this->filesystem->remove($this->tmpWorkDir);
    }

    /**
     * Gets the origin path for a given template.
     *
     * @param string $template Template name.
     *
     * @return string Path to the origin template directory.
     */
    private function getOriginTemplatePath(string $template): string
    {
        $relativePath = 'templates' . DIRECTORY_SEPARATOR . $template;

        switch ($this->mode) {
            case self::MODE_LOCAL:
                if (Phar::running()) {
                    $originTemplatePath = 'phar://' . Phar::running(false) . DIRECTORY_SEPARATOR . $relativePath;
                } else {
                    $originTemplatePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $relativePath;
                }
                break;
            case self::MODE_REMOTE:
                $originTemplatePath = $this->projectDir . DIRECTORY_SEPARATOR . $relativePath;
                break;
            default:
                throw new RuntimeException("Unknown running mode: $this->mode");
        }

        if (!is_dir($originTemplatePath)) {
            throw new RuntimeException(sprintf('Invalid template provided: %s', $originTemplatePath));
        }

        return $originTemplatePath;
    }

    /**
     * Copies the template files from the origin template path to the temporary work path.
     *
     * @param string $template Template name.
     */
    private function checkoutTemplateFiles(string $template): void
    {
        $originTemplatePath = $this->getOriginTemplatePath($template);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($originTemplatePath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo) {
                $relativePath = substr($file->getPathname(), strlen($originTemplatePath) + 1);
                $target       = $this->tmpWorkDir . DIRECTORY_SEPARATOR . $relativePath;

                if ($file->isDir()) {
                    $this->filesystem->mkdir($target);
                } else {
                    $this->filesystem->copy($file->getPathname(), $target);
                }
            }
        }
    }

    /**
     * Retrieves the root path for the new project.
     *
     * @param string $name Name of the new project.
     *
     * @return string The root directory of the project.
     */
    private function getProjectDir(string $name): string
    {
        $currentDir = getcwd();

        if ($currentDir === false) {
            throw new RuntimeException('Unable to get current working directory');
        }

        return $this->mode === self::MODE_LOCAL ? $currentDir . DIRECTORY_SEPARATOR . $name : $currentDir;
    }

    /**
     * Ensures that the project root directory exists, optionally overwriting it if needed.
     *
     * @param bool $force Whether to force overwrite existing directories.
     */
    private function ensureProjectDirEmpty(bool $force): void
    {
        switch ($this->mode) {
            case self::MODE_REMOTE:
                $this->clearDir($this->projectDir);
                break;
            case self::MODE_LOCAL:
                if (!$this->filesystem->exists($this->projectDir)) {
                    $this->filesystem->mkdir($this->projectDir);
                } else {
                    if ($force) {
                        $this->clearDir($this->projectDir);
                    } else {
                        throw new RuntimeException(
                            sprintf('Directory %s already exists. Use --force to overwrite.', $this->projectDir),
                        );
                    }
                }
                break;
        }
    }

    /**
     * Creates a temporary working directory.
     */
    private function prepareTmpWorkDir(): void
    {
        $base   = sys_get_temp_dir();
        $prefix = '_temp_';

        // 安全校验
        if (!is_writable($base)) {
            throw new RuntimeException(sprintf('Temp directory not writable: %s', $base));
        }

        $tmpWorkDir = $base . DIRECTORY_SEPARATOR . $prefix . uniqid();

        try {
            $this->filesystem->mkdir($tmpWorkDir);
            $this->tmpWorkDir = $tmpWorkDir;
        } catch (IOException $e) {
            throw new RuntimeException(
                sprintf('Failed to create temp directory at %s: %s', $tmpWorkDir, $e->getMessage()),
            );
        }
    }

    /**
     * Ensures that the project root directory is empty before proceeding.
     * If the directory is not empty, an exception is thrown to prevent overwriting.
     */
    private function projectDirEmptyOrFail(): void
    {
        $items = $this->getContentsInDir($this->projectDir);

        if (count($items) > 0) {
            throw new RuntimeException(sprintf('%s is not empty. Please retry.', $this->projectDir));
        }
    }

    private function clearDir(string $dir): void
    {
        $files = $this->getContentsInDir($dir);

        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                $this->clearDir($filePath);
                rmdir($filePath);
            } else {
                unlink($filePath);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getContentsInDir(string $dir): array
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("The path is not a valid directory: $dir");
        }

        if (!is_readable($dir)) {
            throw new InvalidArgumentException("The directory is not readable: $dir");
        }

        $result = scandir($dir);

        if ($result === false) {
            throw new RuntimeException('Failed to read directory: ' . $dir);
        }

        return array_diff($result, ['.', '..']);
    }

    /**
     * Safely converts any scalar value to a string.
     *
     * @param mixed $value The value to convert.
     *
     * @return string The string representation of the value.
     */
    private function stringify(mixed $value): string
    {
        return is_scalar($value) && $value !== '' ? (string)$value : '';
    }
}
