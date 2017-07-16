<?php

namespace Deployer;

if (file_exists('.env')) {
    $dotenv = (new \Dotenv\Dotenv(__DIR__))->load();
}

require 'recipe/laravel.php';

/**
 * Collect anonymous stats about Deployer usage for improving developer experience.
 * If you are not comfortable with this, you will always be able to disable this
 * by setting `allow_anonymous_stats` to false.
 */
set('allow_anonymous_stats', getenv('DEPLOYER_STATS'));

// Configuration

set('repository', getenv('DEPLOYER_REPOSITORY'));
set('php_version', getenv('DEPLOYER_PHP_VERSION'));
set('keep_releases', getenv('DEPLOYER_KEEP_RELEASES'));
set('writable_mode', 'chmod');
set('shared_files', []);
set('deploy_path', function () {
    return run('pwd');
});

// Tasks

desc('Setup initial .env for Forge.');
task('init:env', function () {
    if (!test('[ -d .env ]')) {
        copy('.env.example', '.env');
        file_put_contents('.env', preg_replace(
            '/^APP_KEY\=/m',
            'APP_KEY=base64:' . base64_encode(random_bytes(32)),
            file_get_contents('.env')
        ));
    }
    if (!test('[ -d {{deploy_path}}/current ]')) {
        run("{{bin/symlink}} {{deploy_path}}/src {{deploy_path}}/current");
    }
    if (!test('[ -d {{release_path}}/.env ]')) {
        invoke('deploy:env');
    }
});

desc('Link to .env in root folder for Forge editor.');
task('deploy:env', function () {
    run("{{bin/symlink}} {{deploy_path}}/.env {{release_path}}/.env");
});

desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    run('sudo -S service {{php_version}} reload');
});

desc('Check site status after deployment');
task('site:status', function () {
    if (getenv('APP_URL')) {
        $response = \Zttp\Zttp::get(getenv('APP_URL'));
        if ($response->isSuccess()) {
            write("✔ Site is online!\n");
        } else {
            write("✘ Site is offline!\n");
            invoke('rollback');
            invoke('php-fpm:restart');
            throw new \Exception("Site did not load, deployment rolled back");
        }
    } else {
        write("✘ App URL not set in .env\n");
    }

});

desc('Task to run before the deploy');
task('deployer:before', function () {
    customTask('DEPLOYER_BEFORE');
});

desc('Task to run during the deploy');
task('deployer:artisan', function () {
    customTask('DEPLOYER_ARTISAN');
});

desc('Task to run after the deploy');
task('deployer:after', function () {
    customTask('DEPLOYER_AFTER');
});

desc('Deploy your project');
task('forge:deploy', [
    'deployer:before',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:env',
    'deployer:artisan',
    'php-fpm:restart',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'deployer:after'
]);

// Additional task to perform
after('rollback', 'php-fpm:restart');
after('deploy:failed', 'deploy:unlock');
after('deploy', 'success');

/**
 * Helper function to get chained task from .env file
 */
function customTask($env)
{
    if (getenv($env)) {
        $tasks = explode('|', getenv($env));
        foreach ($tasks as $task) {
            invoke($task);
        }
    } else {
        write("✘ No task to perform.\n");
    }
}