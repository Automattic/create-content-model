
# Get Started


Watch our tutorial video [TODO]


## Quick Start

### Data Model Main Workflow

**Data model creation**

* From the Content Models page, click Add New Model.
* Create the custom post type and custom fields in the Editor.
* When adding blocks that will be bound, add placeholder value in the block content.
* Use the Attribute Bindings UI in the BLock Inspector panel to set up bindings.
* Use the Manage Fields UI to manage all post_meta fields.
* Use the special binding key `post_content` with a Group block to generate a bound rich text area.
* Save the data model.


**Data entry**

* Click on the data model name in the wp-admin side bar.
* Populate a few CPT instances with content values.

**Front-end layout**

* Single post
  * You can set up the the single post layout in the CPT template.
  * Alternatively you can create a new MyCPTsingle.html template and add block variations from the block inserter.
* Query loop
  * Create a custom query loop on a page or template (e.g. the theme’s home template) and add block variations from the block inserter.

### Plugin Export Workflow

* Click Content Models > Export.
* Export the data model plugin.
* Import the data model to a new site (or deactivate the main plugin on the current site and import the new plugin).
* Populate the data model in the custom post typel.
  