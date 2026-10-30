<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\CustomerSsoOkta\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts composer.json requires every package the module code and config reference directly.
 */
class ComposerTest extends TestCase
{
    /**
     * Namespace / config-URN prefix → package that provides it.
     */
    private const PACKAGES = [
        'DmLab\\SsoCore\\' => 'dmlab/module-sso-core',
        'DmLab\\CustomerSso\\' => 'dmlab/module-customer-sso',
        'Magento\\Framework\\' => 'magento/framework',
        'urn:magento:framework:' => 'magento/framework',
        'Magento\\Store\\' => 'magento/module-store',
        'urn:magento:module:Magento_Config:' => 'magento/module-config',
    ];

    /** @var array<string, mixed> */
    private array $composer;

    /** @var string */
    private string $moduleRoot;

    protected function setUp(): void
    {
        $this->moduleRoot = dirname(__DIR__, 2);
        $composer = json_decode(
            (string) file_get_contents($this->moduleRoot . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        self::assertIsArray($composer);
        $this->composer = $composer;
    }

    public function testVersionIsSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $this->composer['version'] ?? '');
    }

    public function testRequiresDeclaredDependencies(): void
    {
        foreach ([
            'dmlab/module-sso-core',
            'dmlab/module-customer-sso',
            'magento/module-store',
            'magento/module-config',
            'magento/framework',
        ] as $package) {
            self::assertArrayHasKey($package, $this->composer['require'], $package . ' is not required');
        }
    }

    public function testVersionConstraints(): void
    {
        self::assertSame('~8.2.0||~8.3.0||~8.4.0||~8.5.0', $this->composer['require']['php']);
        self::assertSame('^103.0.7', $this->composer['require']['magento/framework']);
        self::assertSame('^101.1.7', $this->composer['require']['magento/module-store']);
        self::assertSame('^101.2.7', $this->composer['require']['magento/module-config']);
        self::assertSame('^11.5||^12.5', $this->composer['require-dev']['phpunit/phpunit']);
    }

    public function testEveryDirectlyUsedPackageIsRequired(): void
    {
        $used = $this->usedPackages();

        self::assertContains('dmlab/module-sso-core', $used);
        self::assertContains('magento/module-store', $used);
        self::assertContains('magento/module-config', $used);
        foreach ($used as $package) {
            self::assertArrayHasKey($package, $this->composer['require'], $package . ' is used but not required');
        }
    }

    public function testMetadataIsDeclared(): void
    {
        self::assertSame([['name' => 'DMLab', 'homepage' => 'https://dmlab.work']], $this->composer['authors']);
        self::assertSame('https://github.com/dmlab-work/module-customer-sso-okta', $this->composer['homepage']);
        self::assertSame(
            [
                'issues' => 'https://github.com/dmlab-work/module-customer-sso-okta/issues',
                'source' => 'https://github.com/dmlab-work/module-customer-sso-okta',
            ],
            $this->composer['support']
        );
    }

    public function testKeywordsAreDeclared(): void
    {
        foreach (['magento2', 'magento2-module', 'magento 2', 'okta', 'sso'] as $keyword) {
            self::assertContains($keyword, $this->composer['keywords'] ?? [], $keyword . ' keyword is missing');
        }
    }

    public function testCiTestsPhp82To85(): void
    {
        self::assertMatchesRegularExpression(
            "/^\s+php: \['8\.2', '8\.3', '8\.4', '8\.5'\]$/m",
            $this->ciWorkflow()
        );
    }

    public function testCiDisablesPackagistNotifyBeforeInstall(): void
    {
        $ci = $this->ciWorkflow();
        preg_match('/^\s+- run: composer config notify-on-install false$/m', $ci, $notify, PREG_OFFSET_CAPTURE);
        $installs = preg_match_all('/^\s+- uses: ramsey\/composer-install@/m', $ci, $install, PREG_OFFSET_CAPTURE);

        self::assertNotEmpty($notify, 'notify-on-install false step is missing');
        self::assertSame(1, $installs, 'exactly one composer install expected');
        self::assertLessThan($install[0][0][1], $notify[0][1]);
    }

    public function testReadmeScreenshotExists(): void
    {
        $image = $this->moduleRoot . '/.github/images/config.png';

        self::assertFileExists($image);
        self::assertSame(IMAGETYPE_PNG, getimagesize($image)[2] ?? null);
    }

    public function testReadmeSectionsAreInOrder(): void
    {
        $readme = (string) file_get_contents($this->moduleRoot . '/README.md');
        preg_match_all('/^## (.+)$/m', $readme, $matches);

        self::assertSame(
            ['Install', 'Requirements', 'Set up Okta', 'Hyvä', 'Architecture', 'License'],
            $matches[1]
        );
        self::assertStringContainsString('](.github/images/config.png)', $readme);
        self::assertStringContainsString('Magento 2.4.7+ · PHP 8.2–8.5', $readme);
        self::assertLessThan(strpos($readme, '## Install'), strpos($readme, '.github/images/config.png'));
    }

    public function testChangelogTopSectionMatchesVersion(): void
    {
        $changelog = (string) file_get_contents($this->moduleRoot . '/CHANGELOG.md');

        self::assertSame(1, preg_match('/^## (\S+)$/m', $changelog, $match));
        self::assertSame($this->composer['version'] ?? null, $match[1]);
    }

    private function ciWorkflow(): string
    {
        return (string) file_get_contents($this->moduleRoot . '/.github/workflows/ci.yml');
    }

    /**
     * @return list<string>
     */
    private function usedPackages(): array
    {
        $used = [];
        foreach ($this->sourceFiles() as $file) {
            $content = (string) file_get_contents($file);
            foreach (self::PACKAGES as $prefix => $package) {
                if (str_contains($content, $prefix)) {
                    $used[$package] = true;
                }
            }
        }

        return array_keys($used);
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        $files = [$this->moduleRoot . '/registration.php'];
        foreach (['Model', 'etc'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->moduleRoot . '/' . $dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (in_array($file->getExtension(), ['php', 'xml'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
