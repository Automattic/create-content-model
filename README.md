# Data Types with content areas

Run it with `npm install` and `npm run dev-server`.

[Watch the solution walkthrough here](https://www.youtube.com/watch?v=pA_lq7eeGlg).

## How it works

### Creating a data type

Much like the Hackathon, the Editor is used to create a new data type and define its fields.

**The "fields" in this prototype are limited to the content areas.**

When you select a Group block, a new inspector control ("Content area") is added to the sidebar,
where you can bind the contents to a specific part of the post object:

- `post_content` binds to the `post_content` attribute.
- Any other value binds to the post meta fields.

### Entering data

The data type template is displayed in the Editor. User enters content in the content area blocks that were added.

Upon clicking "Save", the back-end will intercept the post, extract the blocks from the content areas and
save them to the appropriate places (e.g., `post_content` and post meta fields.)

### Displaying data

The `post_content` is not intercepted, and the stored content is displayed.

The plugin creates a block variation for each post meta field, and also exposes a block that renders the data type's template in the current Query Loop context.
