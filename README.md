# Create Content Model

Create content models in WP Admin.



### Development

* `npm install` to install the dependencies
* `npm run dev-server` to start the local WordPress server
* open new terminal window
* `npm start` to start the JavaScript bundler watcher

### Bundling

Run `npm run plugin-zip` to create a zip file of the plugin. It will automatically bundle the JavaScript files.


### Usage Hints
(This is temporary, helpful for internal testing)
* If rich text area is needed in a data model, a group block can be added in the CPT-template and bound to `post_content` with the speical key `post_content`
    * <img width="818" alt="image" src="https://github.com/user-attachments/assets/d8bd60ea-858c-42fd-b4f6-7c2295115e08">
