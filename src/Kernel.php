<?php

namespace App;

use App\DependencyInjection\Compiler\DoctrineMigrationsComparatorPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';
    private const MODULE_CONFIG_DIR_PATTERNS = [
        '/modules/*/*/config',
        '/vendor/controleonline/*/config',
    ];

    public function boot(): void
    {
        parent::boot();
        !defined('APP_NAME') ? define('APP_NAME', $this->getContainer()->getParameter('app_name')) : false;
        date_default_timezone_set($this->getContainer()->getParameter('timezone'));
    }

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new DoctrineMigrationsComparatorPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir() . '/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir() . '/config';

        $loader->load($confDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');

        foreach ($this->getModuleConfigDirs() as $configDir) {
            $loader->load($configDir . '/config' . self::CONFIG_EXTS, 'glob');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $routes->import($confDir . '/{routes}/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        $routes->import($confDir . '/{routes}/*' . self::CONFIG_EXTS, 'glob');
        $routes->import($confDir . '/{routes}' . self::CONFIG_EXTS, 'glob');

        foreach ($this->getModuleConfigDirs() as $configDir) {
            $routes->import($configDir . '/routes/*' . self::CONFIG_EXTS, 'glob');
        }
    }

    /**
     * Modules may be installed as local path repositories in modules/ or as
     * regular Composer packages in vendor/. Each module owns its config.
     */
    private function getModuleConfigDirs(): array
    {
        $configDirs = [];
        $packageNames = [];

        foreach (self::MODULE_CONFIG_DIR_PATTERNS as $pattern) {
            $matches = glob($this->getProjectDir() . $pattern, GLOB_ONLYDIR) ?: [];
            sort($matches);

            foreach ($matches as $configDir) {
                $packageDir = \dirname($configDir);
                $packageName = $this->getComposerPackageName($packageDir);

                if ($packageName !== null) {
                    if (isset($packageNames[$packageName])) {
                        continue;
                    }

                    $packageNames[$packageName] = true;
                }

                $configDirs[] = $configDir;
            }
        }

        return $configDirs;
    }

    private function getComposerPackageName(string $packageDir): ?string
    {
        $composerFile = $packageDir . '/composer.json';
        if (!is_file($composerFile)) {
            return null;
        }

        $composer = json_decode((string) file_get_contents($composerFile), true);

        return is_array($composer) && is_string($composer['name'] ?? null) ? $composer['name'] : null;
    }
}
