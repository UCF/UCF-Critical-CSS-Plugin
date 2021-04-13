# UCF Critical CSS Plugin #

Plugin that allows for critical CSS and deferred styles and supports automatic generation via an external service.


## Description ##

The UCF Critical CSS Plugin provides administrators the ability to enqueue specific CSS rules for rendering the above-the-fold portion of pages and then defer the loading of the rest of the styles until after the initial render. The plugin allows for deferred styles on individual pages or on specified post types.


## Documentation ##

Head over to the [UCF Critical CSS Plugin wiki](https://github.com/UCF/UCF-Critical-CSS-Plugin/wiki) for detailed information about this plugin, installation instructions, and more.


## Changelog ##

### 0.1.0 ###
* Initial pre-release for live testing


## Upgrade Notice ##

n/a


## Development ##

Note that compiled, minified css and js files are included within the repo.  Changes to these files should be tracked via git (so that users installing the plugin using traditional installation methods will have a working plugin out-of-the-box.)

[Enabling debug mode](https://codex.wordpress.org/Debugging_in_WordPress) in your `wp-config.php` file is recommended during development to help catch warnings and bugs.

### Requirements ###
* node
* gulp-cli

### Instructions ###
1. Clone the UCF-Critical-CSS-Plugin repo into your local development environment, within your WordPress installation's `plugins/` directory: `git clone https://github.com/UCF/UCF-Critical-CSS-Plugin.git`
2. `cd` into the new UCF-Critical-CSS-Plugin directory, and run `npm install` to install required packages for development into `node_modules/` within the repo
3. Optional: If you'd like to enable [BrowserSync](https://browsersync.io) for local development, or make other changes to this project's default gulp configuration, copy `gulp-config.template.json`, make any desired changes, and save as `gulp-config.json`.

    To enable BrowserSync, set `sync` to `true` and assign `syncTarget` the base URL of a site on your local WordPress instance that will use this plugin, such as `http://localhost/wordpress/my-site/`.  Your `syncTarget` value will vary depending on your local host setup.

    The full list of modifiable config values can be viewed in `gulpfile.js` (see `config` variable).
3. Run `gulp default` to process front-end assets.
4. If you haven't already done so, create a new WordPress site on your development environment to test this plugin against.
5. Activate this plugin on your development WordPress site.
6. Configure plugin settings from the WordPress admin under "UCF Critical CSS".
7. Run `gulp watch` to continuously watch changes to scss and js files. If you enabled BrowserSync in `gulp-config.json`, it will also reload your browser when plugin files change.

### Other Notes ###
* This plugin's README.md file is automatically generated. Please only make modifications to the README.txt file, and make sure the `gulp readme` command has been run before committing README changes.  See the [contributing guidelines](https://github.com/UCF/UCF-Critical-CSS-Plugin/blob/master/CONTRIBUTING.md) for more information.


## Contributing ##

Want to submit a bug report or feature request?  Check out our [contributing guidelines](https://github.com/UCF/UCF-Critical-CSS-Plugin/blob/master/CONTRIBUTING.md) for more information.  We'd love to hear from you!
