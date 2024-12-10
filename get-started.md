
# Get Started
Start leveraging the latest WordPress core features with WordPress.com’s experimental Create Content Model plugin. 

Create custom post types and custom fields directly in the Block Editor, and then export your data model and data entry UI as a standalone, maintenance-free plugin. Everything you build is using core WordPress functionality, so you can run the future-proof plugin or you can use filters and hooks to extend and adapt your data model.

To get started with the Create Content Model plugin, [download the latest release](https://github.com/Automattic/create-content-model/releases/latest/download/create-content-model.zip), [launch our WordPress Playground Blueprint](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/create-content-model/trunk/blueprint.json), or [test locally with Studio](#test-locally-with-studio).

https://github.com/user-attachments/assets/09b449f0-4398-4037-ba07-820c76407d7d

## Creating a content model

A content model is a custom post type that you can register and build directly in WordPress core. As you design it, you select which blocks are editable and can add custom fields.

Creating a new content model includes:

1. Registering a new post type.
2. Designing the frontend template for your new post type and selecting which blocks in the template are editable by adding new “Bindings.”
3. Adding custom fields to your post to collect additional data.

*Note that custom taxonomy support is on the [roadmap](https://github.com/Automattic/create-content-model/issues/77).*

### Register the post type 

From the Content Models page:

1. Click **Add New Model**.
2. Name your content model.
3. Manage the post type settings using the sidebar panel: Singular Label, Plural Label, Icon Name

![data-model-post-type-labels](https://github.com/user-attachments/assets/9369283f-d8d9-4040-8ec3-722ef8b9d0ff)

### Design the frontend template for your new post type
Once you’ve created the post type, you can start designing the template.

As you add blocks to the template, they will not be editable by default. You’ll need to select which blocks include dynamic (aka editable) data by selecting **Add Binding** in the block sidebar controls. 

Once a block is “bound,” it will be editable in the future, and any data entered will be automatically stored as postmeta, meaning you can use that data in different templates or query loops, view it in the REST API, bulk manage it via the database, and much more. 

![data-model-add-binding](https://github.com/user-attachments/assets/7a93ce88-f241-4017-bc01-1ecb472164b1)

If you have the [Gutenberg plugin](https://wordpress.org/plugins/gutenberg/) and enable the "Block Binding UI" experiment enabled, you can view and use your custom fields registered as postmeta when you manually bind an attribute.

Since we’re using core WordPress’ [Block Bindings API](https://make.wordpress.org/core/2024/03/06/new-feature-the-block-bindings-api/), the only blocks that are currently supported are the [Paragraph](https://wordpress.org/documentation/article/paragraph-block/), [Heading](https://wordpress.org/documentation/article/heading-block/), [Image](https://wordpress.org/documentation/article/image-block/), and [Buttons](https://wordpress.org/documentation/article/buttons-block/) blocks.

### Rich content areas: the Group block
This tool also allows you to bind the [Group](https://wordpress.org/documentation/article/group-block/) block to a post meta field, which creates a “rich text” or WYSIWYG area in your template where multiple blocks can be used. A bound group block will store its contents in a post meta field, or you can map it directly to the `post_content` attribute. 

![data-model-attribute-bindings](https://github.com/user-attachments/assets/6dfd750a-315b-46cd-ac73-4426b8e7a54f)

## Your data model and custom fields
All of your bound blocks will save their content to post meta fields, so you can redesign, remix, and filter your content model in the future and without losing the integrity of your data. Open the Post Meta sidebar panel to view.

![data-model-post-meta](https://github.com/user-attachments/assets/7232d9b7-8ac3-4159-ba4a-6e94d37ada58)

Click the Manage Post Meta button to browse all of your block bindings and create your own custom fields that are available in the post editing screen.

![data-model-post-meta-custom-fields](https://github.com/user-attachments/assets/f7ee2af7-1753-41ec-885d-4cf9b3669a93)

Click **Publish** to see your new content model on your website.

## Adding and managing content
Once your data model is published, it will show up as an additional post type beneath Posts and Pages in your WordPress dashboard. 

Add new content just like you would add any other post content. You’ll notice that only the blocks you bound are editable, and the rest of the data model’s template is safe from being changed or edited.

![data-model-content-group](https://github.com/user-attachments/assets/eac3b513-175b-480b-9777-94fa6cc340b1)

Any custom fields that you’ve added will also be available in the post sidebar:

![data-model-custom-fields](https://github.com/user-attachments/assets/39b485a1-cf3a-492a-a497-969d1ca14040)

Click the **Publish** button to publish your post and view it on the front end. 

## Updating the front-end layout
Create Content Model is designed to work with the block editor and block-based themes. If you’d like to make changes to the design of the single or archive templates for your custom post type, you can do that inside of the Site Editor, just like you would for any other post type. 

### Single post template
You can set up the single post layout in your custom post type template. Alternatively, you can create a new `single-CPTNAME.html` template and add block variations from the block inserter.

### Archives and the Query Loop block
Create a custom [Query Loop](https://wordpress.org/documentation/article/query-loop-block/) on a page, `archive-CPTNAME.html` template, or any other template (e.g. the theme’s home template). Pull in the posts from your data model, sort and filter them, and use the new block variations in the block inserter to pull individual pieces of data into your template.

![data-model-query-loop](https://github.com/user-attachments/assets/a5023781-4ce8-426f-9e9d-46eb7ce35795)

## Plugin export workflow
Create Content Model is a development tool, but it’s not required to run on your site. If you’re done building your content model, you can “export” it as a standalone plugin:

1. Click Content Models → **Export**.
2. Export the data model plugin by clicking the **Download ZIP file** button.
3. Install your data model plugin on a new site (or deactivate the main plugin on the current site and import the new plugin). Remember to upload it as the .zip file.
4. Optionally, add version control for your own content model plugin to track changes and deploy your plugin across multiple sites.

## Test locally with Studio
Studio is WordPress.com's free, open-source local development environment.

1. Download [Studio](https://developer.wordpress.com/studio/?utm_source=github&utm_medium=get-started&utm_campaign=create-content-model).
2. Add a site.
3. Open WP Admin.
4. Download the [latest plugin release](https://github.com/Automattic/create-content-model/releases/latest/download/create-content-model.zip).
5. Install and activate the plugin.
