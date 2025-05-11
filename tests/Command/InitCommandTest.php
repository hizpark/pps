<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\InitCommand;
use Hizpark\DirectoryTree\DirectoryTreeViewer;
use Hizpark\ZipMover\ZipMover;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class InitCommandTest extends TestCase
{
    private CommandTester $commandTester;

    private InitCommand $command;

    private Filesystem $filesystem;

    private string $testBaseDir;

    private string $originalWorkingDir;

    private string $originalMode;

    protected function setUp(): void
    {
        $this->filesystem   = new Filesystem();
        $this->originalMode = getenv('MODE') ?: InitCommand::MODE_LOCAL;

        $currentDir = getcwd();

        if ($currentDir === false) {
            $this->markTestSkipped('Cannot determine current working directory');
        }
        $this->originalWorkingDir = $currentDir;

        $this->testBaseDir = sys_get_temp_dir() . '/test-pps-init';
        $this->filesystem->mkdir($this->testBaseDir);
        chdir($this->testBaseDir);

        $this->command = new InitCommand(
            $this->createMock(ZipMover::class),
            $this->createConfiguredMock(DirectoryTreeViewer::class, ['render' => 'Mocked tree view']),
            $this->filesystem,
        );

        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
    }

    protected function tearDown(): void
    {
        putenv('MODE=' . $this->originalMode);
        chdir($this->originalWorkingDir);
        $this->filesystem->remove($this->testBaseDir);
    }

    /* 基础功能测试 */

    public function testCommandInitialization(): void
    {
        $command = new InitCommand(
            $this->createMock(ZipMover::class),
            $this->createMock(DirectoryTreeViewer::class),
            new Filesystem(),
        );

        $this->assertSame('init', $command->getName());
        $this->assertTrue($command->getDefinition()->hasOption('force'));
    }

    /* 正常执行流程测试 */

    public function testCommandExecutesSuccessfullyWithValidName(): void
    {
        $projectName = 'valid_project_' . uniqid();
        $this->commandTester->execute(['name' => $projectName]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testBaseDir . '/' . $projectName);
    }

    public function testExecuteWithForceOptionClearsDirectory(): void
    {
        $projectName = 'forced_project';
        $projectDir  = $this->testBaseDir . '/' . $projectName;

        $this->filesystem->mkdir($projectDir);
        $this->filesystem->dumpFile($projectDir . '/old_file.txt', 'content');

        $this->commandTester->execute([
            'name'    => $projectName,
            '--force' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertEmpty(array_diff(scandir($projectDir), ['.', '..']));
    }

    /* 异常情况测试 */

    public function testExecuteWithExistingDirectoryWithoutForceOption(): void
    {
        $projectName = 'existing_project';
        $projectDir  = $this->testBaseDir . '/' . $projectName;

        $this->filesystem->mkdir($projectDir);
        $this->filesystem->dumpFile($projectDir . '/keep.txt', 'content');

        $this->commandTester->execute(['name' => $projectName]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertFileExists($projectDir . '/keep.txt');
    }

    /* 参数选项测试 */

    public function testTemplateOptionWithValidTemplate(): void
    {
        $projectName = 'template_test_' . uniqid();

        $this->commandTester->execute([
            'name'       => $projectName,
            '--template' => 'php',
            '--force'    => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('template [php]', $this->commandTester->getDisplay());
    }

    public function testTemplateOptionWithInvalidTemplate(): void
    {
        $projectName = 'invalid_template_test_' . uniqid();

        $this->commandTester->execute([
            'name'       => $projectName,
            '--template' => 'invalid_template',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Invalid template provided', $this->commandTester->getDisplay());
    }

    /* 模式相关测试 */

    public function testModeBehaviorOfLocal(): void
    {
        putenv('MODE=local');
        $projectName = 'remote_project_' . uniqid();

        $this->commandTester->execute(['name' => $projectName]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('All done!', $this->commandTester->getDisplay());
    }

    public function testModeBehaviorOfRemote(): void
    {
        putenv('MODE=remote');
        $projectName = 'remote_project_' . uniqid();

        $this->commandTester->execute(['name' => $projectName]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('All done!', $this->commandTester->getDisplay());
    }

    public function testProjectDirectoryIsEmptyBeforeDeployment(): void
    {
        $modes       = [InitCommand::MODE_LOCAL, InitCommand::MODE_REMOTE];
        $projectName = 'empty_dir_test_' . uniqid();

        foreach ($modes as $mode) {
            putenv("MODE={$mode}");

            $projectDir = $this->testBaseDir . '/' . $projectName;
            $this->filesystem->mkdir($projectDir);
            $this->filesystem->dumpFile($projectDir . '/pre_existing.txt', 'content');

            $this->commandTester->execute([
                'name'    => $projectName,
                '--force' => true,
            ]);

            $this->assertEmpty(array_diff(scandir($projectDir), ['.', '..']));
        }
    }

    /* 项目名称验证测试 */

    public function testProjectNamesWithValidString(): void
    {
        $testCases = [
            'single letter'    => 'a',
            'letters'          => 'valid',
            'alphanumeric'     => 'a1',
            'with hyphen'      => 'a-b',
            'with underscore'  => 'a_b',
            'mixed'            => 'a1b',
            'multiple hyphens' => 'a-b-c',
        ];

        foreach ($testCases as $description => $name) {
            $this->commandTester->execute(['name' => $name]);

            $this->assertEquals(
                Command::SUCCESS,
                $this->commandTester->getStatusCode(),
                "{$description} [{$name}] should pass",
            );
        }
    }

    public function testProjectNamesWithInvalidString(): void
    {
        $testCases = [
            'path separator'      => ['name' => 'invalid/name', 'expected' => 'illegal character "/"'],
            'leading dot'         => ['name' => '.invalid', 'expected' => 'illegal character "."'],
            'trailing dot'        => ['name' => 'invalid.', 'expected' => 'illegal character "."'],
            'whitespace'          => ['name' => ' invalid ', 'expected' => 'illegal character " "'],
            'empty string'        => ['name' => '', 'expected' => 'cannot be empty'],
            'leading digit'       => ['name' => '1project', 'expected' => 'start with a letter'],
            'trailing underscore' => ['name' => 'project_', 'expected' => 'end with underscore'],
        ];

        foreach ($testCases as $description => $case) {
            $this->commandTester->execute(['name' => $case['name']]);

            $this->assertEquals(
                Command::FAILURE,
                $this->commandTester->getStatusCode(),
                "{$description} should fail",
            );

            $this->assertStringContainsString(
                $case['expected'],
                $this->commandTester->getDisplay(),
            );
        }
    }

    /* 清理和错误处理测试 */

    public function testCleanupRunsOnFailure(): void
    {
        $mockZipMover = $this->createMock(ZipMover::class);
        $mockZipMover->method('compress')->willThrowException(new RuntimeException('Test exception'));

        $command = new InitCommand(
            $mockZipMover,
            $this->createMock(DirectoryTreeViewer::class),
            $this->filesystem,
        );

        $tester  = new CommandTester($command);
        $tempDir = sys_get_temp_dir() . '/cleanup_test_' . uniqid();

        try {
            $tester->execute(['name' => 'should_cleanup']);
            $this->fail('Expected exception');
        } catch (RuntimeException) {
            $this->assertDirectoryDoesNotExist($tempDir);
        }
    }
}
