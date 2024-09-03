
# Get Started

## Quick Start Guide

https://github.com/user-attachments/assets/50f2b533-a412-4f40-ae21-17af0b90c39d

#### Create Data Model

* From the Content Models page, click Add New Model.
* Name the model.
* Add a block and a placeholder value.
* Use the Attribute Bindings UI in the Block Inspector panel to set up bindings.
* Save the data model, and the data model will show up in the WP Admin sidebar.

#### Data Entry

* Click on the data model name in the WP Admin sidebar.
* Create a few instances of the data model.

#### Usage in Query Loops

* In a new Page/Post/Template, add a Query Loop Block.
* Select the Post Type for the Query Loop.
* Tweak the template to your liking.

#### Export Workflow

* Click Content Models > Export.
* Click on "Download ZIP file" to get the standalone plugin.
* Install and activate the standalone plugin on a new site (or deactivate the main plugin on the current site and install the standalone plugin).
* The defined data model will show up in the WP Admin sidebar.
* You can start creating instances of the data model.


## Advanced usage

#### Manage block binding fields
* Use the Manage Fields UI to manage all post_meta fields.
* Use the special binding key `post_content` with a Group block to generate a bound rich text area.
* Save the data model.

#### Front-end layout

* Single post
  * You can set up the the single post layout in the CPT template.
  * Alternatively you can create a new MyCPTsingle.html template and add block variations from the block inserter.
* Query loop
  * Create a custom query loop on a page or template (e.g. the theme’s home template) and add block variations from the block inserter.
