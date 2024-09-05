# Create Content Model

_Define custom post types & fields in the Block Editor._

WordPress.com’s experimental Create Content Model plugin transforms the way custom post types and custom fields are created and managed in WordPress by making use of the latest core features to bring content modeling into the Block Editor. Additionally, the created data model and data entry UI can be exported as a standalone, maintenance-free plugin.

[![Try in WordPress Playground](https://img.shields.io/badge/Try%20in%20WordPress%20Playground-blue?style=for-the-badge)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/create-content-model/trunk/blueprint.json)

You can also test out the plugin locally with [Studio](https://developer.wordpress.com/studio/)! Check out the [Get Started](/GETSTARTED.md#test-locally-with-studio) guide for details.

https://github.com/user-attachments/assets/7fbe9162-6a95-4340-9f7e-456b3fa70471

## Getting Started

Find detailed instructions on creating your content model using this plugin in the [Get Started](/GETSTARTED.md) guide.

[![Download Latest Release](https://img.shields.io/badge/Download%20Latest%20Release-blue?style=for-the-badge)](https://github.com/Automattic/create-content-model/releases/latest/download/create-content-model.zip)

## About

Our team at WordPress.com is excited to share our recent prototyping efforts on game changing approaches to custom content creation. 

The Create Content Model plugin builds upon our custom post types project at the [CloudFest Hackathon in 2024](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-variations/). We’ve leveraged core functionality, like [block bindings](https://make.wordpress.org/core/2024/03/06/new-feature-the-block-bindings-api/) and [block variations](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-variations/), to create a new paradigm for creating and managing custom post types and custom fields in WordPress. 

Unlike existing custom post type and custom field plugins, our plugin takes a native approach by putting the creation and management of these elements directly in the WordPress Block Editor. Using the Block Bindings API, `post_meta` fields are bound to block attributes. Block variations are created from each bound block for use in front-end template layouts. The result is an extremely intuitive workflow for both the setup of custom post types and fields and their usage in front-end templating.

A key feature of the Create Content Model plugin is the export of a locked custom data model and a data entry UI. Developers can generate and reuse the same content models on multiple sites without ongoing plugin maintenance or costs. They can hand off fully functional sites with locked custom post types and fields, ready for clients to populate the content.

## Development

* Run `npm install` to install the dependencies
* Run `npm run dev-server` to start the local WordPress server
* In a new terminal window, run `npm start` to start the JavaScript bundler watcher

### Bundling

Run `npm run plugin-zip` to create a zip file of the plugin. This will automatically bundle the JavaScript files.

### Creating a new release

Create a new release by filling the form on [this page](https://github.com/Automattic/create-content-model/releases/new).

The release title and tag ("Choose a tag" selectbox, above the title) should be in the Semver format (`major.minor.patch`).

The release description should be a list of bullet points of the most meaningful changes. You can copy the commit title from the merged PRs.

After clicking "Publish release," a [GitHub workflow](https://github.com/Automattic/create-content-model/blob/trunk/.github/workflows/release.yml) will bundle the plugin and export the release artifact.

## Contribute & Contact

Want to help us move this concept forward?

Feel free to open an issue in the repo to discuss your proposed improvement. Pull requests are welcome for bug fixes and enhancements.

We built this as a prototype and may invest into it further based on level of interest. Our near term vision is outlined in this [roadmap issue](https://github.com/Automattic/create-content-model/issues/77).

## Licensing
[GNU General Public License](/LICENSE.md)

## Credits & Acknowledgements
We’d like to thank the team at WordPress.com who made this project possible: [Luis Felipe Zaguini](https://github.com/zaguiini), [Candy Tsai](https://github.com/candy02058912), [Autumn Fjeld](https://github.com/autumnfjeld), [Brian Coords](https://github.com/bacoords), [Daniel Bachhuber](https://github.com/danielbachhuber).

## Stay in the Loop with WordPress.com
Follow us:

[![LinkedIn](https://img.shields.io/badge/LinkedIn-0077B5?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/showcase/wordpress.com)

[![X](https://img.shields.io/badge/X-000000?style=for-the-badge&logo=x&logoColor=white)](https://x.com/wordpressdotcom)

[![image](https://img.shields.io/badge/Instagram-E4405F?style=for-the-badge&logo=instagram&logoColor=white)](https://www.instagram.com/wordpressdotcom)

<!-- Later we can add developers newsletter -->

And while you’re at it, check out our [WordPress hosting solution for developers](https://wordpress.com/hosting) and [our agency program](https://wordpress.com/for-agencies/).
