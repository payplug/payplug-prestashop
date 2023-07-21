========================================
Prestashop payment module using Payplug
========================================

Installation
------------

You should use Prestashop module importation in Prestashop admin pages

Plugin UI Library
---------

The plugin ui library use to display the admin configuration is display Vue.Js.  
To run the library on locola there is few requirement. 

### Update the vue.config.js file

To use the lib on local environnement, you have to configure the webpack server proxy.
You must add the line bellow in your `vue.config.js` file. 

    ...
    module.exports = defineConfig({
        ...
        devServer: {
            proxy:  {
                "^[route]": {
                    target: "[serverUrl]",
                    changeOrigin: true,
                    logLevel: "debug",
                    pathRewrite: { "^[pathRewrite]": "[pathRewrite]" }
                },
            }
        },
        ...


Update the different element for your usage
- `route`: the element at the begining of your path
- `severUrl`: your localhost server, including a port
- `pathRewrite`: to adapt is you have a rewrite url setted

There is an example :

    ...
    module.exports = defineConfig({
        ...
        devServer: {
            proxy:  {
                "^/admin-dev": {
                    target: "https://localhost:9080",
                    changeOrigin: true,
                    logLevel: "debug",
                    pathRewrite: { "^/admin-dev": "/admin-dev" }
                },
            }
        },
        ...

For more information, there is the [Vue.Js documentation](https://cli.vuejs.org/config/#devserver-proxy)

### Update the store.js file
Since we need dynamic url for Prestashop CMS, we need to give the admin Url to the Vue.Js lib to make it work. 
TO do it, update in the file `/src/store/store.js` the line :

    ajax_url: window.payplug_admin_config && window.payplug_admin_config.ajax_url ? window.payplug_admin_config.ajax_url : "",

By adding your url at the end
    
    ajax_url: window.payplug_admin_config && window.payplug_admin_config.ajax_url ? window.payplug_admin_config.ajax_url : "[url]",
    
There is an example :

    ajax_url: window.payplug_admin_config && window.payplug_admin_config.ajax_url ? window.payplug_admin_config.ajax_url : "/admin-dev/index.php?controller=AdminPayplug&token=c53743c9dea09997bfb2d06ee2a4612a",

You also could replace all the line :

    ajax_url: '/admin-dev/index.php?controller=AdminPayplug&token=c53743c9dea09997bfb2d06ee2a4612a',


### Update the library
When your development are done, it last the update of your branch.
To do it, go on your payplug-ui project with your terminal then run theses commands :  

    npx vue-cli-service build --mode prestashop && rm -rf [module_path]/dev/dist/payplug/views/ && cp -R [lib_path]/dist/ [module_path]/dev/dist/payplug/views/
    npx vue-cli-service build --mode pspaylater && rm -rf [module_path]/dev/dist/pspaylater/views/ && cp -R [lib_path]/dist/ [module_path]/dev/dist/pspaylater/views/

- `lib_path`: Must be replace by your lib vue.js project path
- `module_path`: Must be replace by your module project path 

There an example of usage:

    npx vue-cli-service build --mode prestashop && rm -rf ~/Sites/docker-module/build/modules/dev/dist/payplug/views/ && cp -R ~/Sites/payplug-ui-plugins/dist/ ~/Sites/docker-module/build/modules/dev/dist/payplug/views/
    npx vue-cli-service build --mode pspaylater && rm -rf ~/Sites/docker-module/build/modules/dev/dist/pspaylater/views/ && cp -R ~/Sites/payplug-ui-plugins/dist/ ~/Sites/docker-module/build/modules/dev/dist/pspaylater/views/

It is possible to use npm as alternative, adapte the following example to your need :

    cp .env.prestashop .env && npm run build && rm -rf ~/Sites/docker-module/build/modules/dev/dist/payplug/views/ && cp -R ~/Sites/payplug-ui-plugins/dist/ ~/Sites/docker-module/build/modules/dev/dist/payplug/views/ 
    cp .env.pspaylater .env && npm run build && rm -rf ~/Sites/docker-module/build/modules/dev/dist/pspaylater/views/ && cp -R ~/Sites/payplug-ui-plugins/dist/ ~/Sites/docker-module/build/modules/dev/dist/pspaylater/views/
    
NB: Use npm nor npx command will generate `.map` files who could be helpfull for the development of feature.

Webpack
---------
Webpack is automatically installed when running the "composer install" command.

To use webpack on this project in a local environment,  you'll only have to use the command "webpack" at the root of the project to compile files.
If you're developing you can use it as a file watcher using the command "webpack --watch".