# Forge Deployer

A simple zero-downtime deployment tool for [Laravel Forge](https://forge.laravel.com/) using [Deployer](https://deployer.org)



## Features

* Configuration options are set via the .env file
* Automatic roll-back if site appears offline after deployment



## Usage

Proceed to create a new site in Forge as you normally would do, but set the web directory   to `/current/public`.

After the site has been provisioned head on over the site details and install this Git Repository  `est73/forge-deployer` with `Install Composer Dependencies` checked.

Once the initial deploy is done edit the environment file and app settings to make these necessary changes.

* Forge Environment setting:
  * `APP_URL` *Site url*

  * `DEPLOYER_REPOSITORY` *Your repository to deploy*

  * `DEPLOYER_PHP_VERSION` *PHP version on the Forge server as noted in the Forge Deploy Script.*

     *Example:* `php7.1-fpm`

* Forge Apps setting:

  * Deployment

    * Make sure `Quick Deploy` is **OFF**

  * Deploy Script

    * Update the script to add our Deployer command.

      *Note the default Forge script has been commented out after the directory change.*

      ```shell
      cd /home/forge/example.com
      #git pull origin master
      #composer install --no-interaction --prefer-dist --optimize-autoloader
      #echo "" | sudo -S service php7.1-fpm reload

      #if [ -f artisan ]
      #then
      #    php artisan migrate --force
      #fi

      # Initiate Deployer
      php vendor/bin/dep forge:deploy
      ```



## Deploying a project

To initiate a deployment press the  `DEPLOY NOW` button in Forge.

(Optional) Add the `Deployment Trigger URL` to your production repository for automatic deployments.



## Deploy Task

Additional task helpers can be added to the default deploy under these variables:

* `DEPLOYER_BEFORE` *Task to run before the deploy*
* `DEPLOYER_ARTISAN` *Artisan commands during the deploy*
* `DEPLOYER_AFTER` *Task to run after the deploy*

Task can be chained together with the usage of a vertical bar `|`.
*Example:* `artisan:up|artisan:migrate|artisan:down`



## Available Task Helpers

* `artisan:up` *Disable maintenance mode*
* `artisan:down` *Enable maintenance mode*
* `artisan:migrate` *Execute artisan migrate*
* `artisan:migrate:rollback` *Execute artisan migrate:rollback*
* `artisan:migrate:status` *Execute artisan migrate:status*
* `artisan:db:seed` *Execute artisan db:seed*
* `artisan:cache:clear` *Execute artisan cache:clear*
* `artisan:config:cache` *Execute artisan config:cache*
* `artisan:route:cache` *Execute artisan route:cache*
* `artisan:view:clear` *Execute artisan view:clear*
* `artisan:optimize` *Execute artisan optimize*
* `artisan:queue:restart` *Execute artisan queue:restart*
* `artisan:storage:link` *Execute artisan storage:link*
* `site:status` *Check site status, and rollback deploy if down*



## Tips and Tricks

* Example `.env` for deploy helper task:

  ```shell
  DEPLOYER_BEFORE=
  DEPLOYER_ARTISAN=artisan:optimize|artisan:route:cache|artisan:config:cache|artisan:migrate|artisan:storage:link
  DEPLOYER_AFTER=site:status
  ```

* If your deploy fails with this error:

  ```shell
  [Deployer\Exception\GracefulShutdownException]  
  Deploy locked.                                  
  Execute "dep deploy:unlock " to unlock.
  ```

  Add this to your `.env` (Just make sure to remove it later after the deploy is successful)

  ```shell
  DEPLOYER_BEFORE=deploy:unlock
  ```