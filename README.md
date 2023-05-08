# MagentoSentry tracing & errors plugin

This is meant to be one example of how Sentry tracing could be implemented for Magento2. This plugin is not officially supported by Sentry. 

It should NOT be adopted for use in production software without first undergoing full review and rigorous testing. This code is provided on an "AS-IS" basis without warranty of any kind, either express or implied, including without limitation any implied warranties of condition, uninterrupted use, merchantability, fitness for a particular purpose, or non-infringement. The details of your application or component, architecture of the host-application, and your target browser support, among many other things, may require you to modify this code. Issues regarding these code examples should be submitted through GitHub.

## Instrumentation:

This plugin instruments the following with Sentry:

`Magento\GraphQl\Controller\GraphQl` 
`Magento\Webapi\Controller\Rest`
`Magento\Framework\AppInterface`


## Setup:

1. Copy repo to `app/code/<vendor-name>/<module>`

2. Config will automatically pull `dsn` and `traces_sample_rate` from `Magento\Framework\App\DeploymentConfig` in `app/etc/env.php`

Example (`app/etc/env.php`): 

> ```'sentry' => ['dsn' => 'myDsn', 'traces_sample_rate' => 0.2]```

This can be expanded to include more options. Or options can be added to `Sentry.init` maually for now.


## Dependencies:

1. This was developed using Magento2 (2.4.5-p1 community) available [here](https://github.com/markshust/docker-magento).

2. Sentry php version ^3.0
3. Developed on macOS Monterey 12.6
4. PHP v 8.1.6


## DEV Notes:

1. Follow instructions here to setup a dev environment [here](https://github.com/markshust/docker-magento)
2. [Docker Desktop for Mac](https://docs.docker.com/desktop/install/mac-install/)
3. Some helpful notes:
    1. Successful setup should copy files from the docker container to local `Sites/magento` directory.
    2. Changes made locally to files can be copied back into the container using `bin/copytocontainer src/app/code/MyVendor`
    3. `bin/restart` - to restart containers
    4. `bin/magento setup:upgrade` - clears compiled code and the cache
    5. Plugin in app/code vs /vendor. Composer will install modles in `vendor/<vendor-name>/<module>`. Since we are developing a plugin, code lives in `app/code/<vendor-name>/<module>`.
    6. Testing distributed tracing requires enabiling CORS in `nginx.config` in container root directory at PHP entry point for main application.
    7. [Magento request routing](https://developer.adobe.com/commerce/php/development/components/routing/)



## Optimizations: 

[Php web server optimizations & sentry](https://docs.sentry.io/platforms/php/performance/#improve-response-time)
# MagentoSentry
