<?php

declare(strict_types=1);

namespace RoaveE2ETest\YouAreUsingItWrong;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class SimulatedInstallationTest extends TestCase
{
    /** @var string|null */
    private $repository;

    protected function tearDown() : void
    {
//        if ($this->repository !== null) {
//            (new Process(['rm', '-r', $this->repository]))
//                ->mustRun();
//        }

        parent::tearDown();
    }

    public function testRepositoryWithoutIssues() : void
    {
        $this->repository = GenerateRepository::generateRepository();

        $command = (new Process([__DIR__ . '/../../vendor/bin/composer', 'install'], $this->repository))
            ->mustRun();

        $output = $command->getOutput();

        self::assertStringNotContainsString('No files analyzed', $output);
        self::assertStringContainsString('checking strictly type-checked packages...', $output);
        self::assertStringContainsString('No errors found!', $output);
        self::assertStringContainsString('done checking strictly type-checked packages', $output);
    }

    public function testRepositoryWithDependenciesNotDependingOnStrictTypeChecksWillNotHaveIssuesRaised() : void
    {
        $this->repository = GenerateRepository::generateRepository('test/repository-not-depending-on-type-checks');

        $command = (new Process([__DIR__ . '/../../vendor/bin/composer', 'install'], $this->repository))
            ->mustRun();

        $output = $command->getOutput();

        self::assertStringNotContainsString('No files analyzed', $output);
        self::assertStringContainsString('checking strictly type-checked packages...', $output);
        self::assertStringContainsString('No errors found!', $output);
        self::assertStringContainsString('done checking strictly type-checked packages', $output);
    }

    public function testRepositoryReportsIssuesWhenDependingOnPackageThatEnforcesStrictTypeChecks() : void
    {
        $this->repository = GenerateRepository::generateRepository('test/repository-depending-on-type-checks');

        $command = new Process([__DIR__ . '/../../vendor/bin/composer', 'install'], $this->repository);

        $command->run();

        self::assertSame(1, $command->getExitCode());

        $output = $command->getOutput();

        self::assertStringContainsString('checking strictly type-checked packages...', $output);
        self::assertStringContainsString(' - test/repository-depending-on-type-checks', $output);
        self::assertStringContainsString(' - - Test\\RepositoryDependingOnTypeChecks\\', $output);
        self::assertStringContainsString('1 errors', $output);
        self::assertStringContainsString(
            'Argument 1 of Test\\RepositoryDependingOnTypeChecks\\SomeClass::amethod expects string, int(123) provided',
            $output
        );

        self::assertStringNotContainsString('No errors found!', $output);
        self::assertStringNotContainsString('done checking strictly type-checked packages', $output);
    }

    public function testRepositoryReportsIssuesWhenDependingIndirectlyOnPackageThatEnforcesStrictTypeChecks() : void
    {
        $this->repository = GenerateRepository::generateRepository('test/repository-indirectly-depending-on-type-checks');

        $command = new Process([__DIR__ . '/../../vendor/bin/composer', 'install'], $this->repository);

        $command->run();

        self::assertSame(1, $command->getExitCode());

        $output = $command->getOutput();

        self::assertStringContainsString('checking strictly type-checked packages...', $output);
        self::assertStringContainsString(' - test/repository-depending-on-type-checks', $output);
        self::assertStringContainsString(' - - Test\\RepositoryDependingOnTypeChecks\\', $output);
        self::assertStringContainsString('1 errors', $output);
        self::assertStringContainsString(
            'Argument 1 of Test\\RepositoryDependingOnTypeChecks\\SomeClass::amethod expects string, int(123) provided',
            $output
        );

        self::assertStringNotContainsString('No errors found!', $output);
        self::assertStringNotContainsString('done checking strictly type-checked packages', $output);
    }

    public function testRepositoryOnlyReportsIssuesOnDependencyUsagesThatEnforceStrictTypeChecks() : void
    {
        $this->repository = GenerateRepository::generateRepository(
            'test/empty-repository',
            'test/repository-depending-on-type-checks',
            'test/repository-indirectly-depending-on-type-checks',
            'test/repository-not-depending-on-type-checks',
        );

        $command = new Process([__DIR__ . '/../../vendor/bin/composer', 'install'], $this->repository);

        $command->run();

        self::assertSame(1, $command->getExitCode());

        $output = $command->getOutput();

        self::assertStringContainsString('checking strictly type-checked packages...', $output);
        self::assertStringContainsString(' - test/repository-depending-on-type-checks', $output);
        self::assertStringContainsString(' - - Test\\RepositoryDependingOnTypeChecks\\', $output);
        self::assertStringContainsString('1 errors', $output);
        self::assertStringContainsString(
            'Argument 1 of Test\\RepositoryDependingOnTypeChecks\\SomeClass::amethod expects string, int(123) provided',
            $output
        );

        self::assertStringNotContainsString('No errors found!', $output);
        self::assertStringNotContainsString('done checking strictly type-checked packages', $output);
    }
}
