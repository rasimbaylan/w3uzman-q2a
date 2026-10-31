# W3Uzman Q2A

### A community platform shaped over years of development

W3Uzman is a customized platform built on [Question2Answer](https://www.question2answer.org/). Developed over several years, it evolved from a Q&A community into a broader news and content experience with custom layouts, editors, and web tools. This repository preserves source snapshots so developers can explore that evolution.

> **Archive in progress:** These snapshots are filtered historical source, not verified releases. Installation, completeness, security, and third-party licenses are still under review. Do not deploy them as-is.

## Explore the history

| Snapshot | Archive | Explore source |
| --- | --- | --- |
| **2019** | W3Uzman archive dated 24 October 2019 | [Browse 2019 snapshot](https://github.com/rasimbaylan/w3uzman-q2a/tree/ab3d159) |
| **2022** | W3Uzman hosting backup dated 6 June 2022 | [Browse 2022 snapshot](https://github.com/rasimbaylan/w3uzman-q2a/tree/25364ed) |
| **2026 · September** | Desktop W3Uzman backup folder dated 6 September 2026 | [Browse September snapshot](https://github.com/rasimbaylan/w3uzman-q2a/tree/47f240d) |
| **2026 · Development copy** | Later Q2A-based W3Uzman copy from the local development environment | [Browse latest snapshot](https://github.com/rasimbaylan/w3uzman-q2a/tree/main) |

The commit dates show when the snapshots were imported into GitHub, not when the original development happened. The current branch contains the later local development copy. Archive and folder dates do not establish when each individual change was developed.

## How the snapshots differ

The surviving source shows a platform evolving across Q2A versions, themes, languages, and bundled extensions:

| Area | 2019 snapshot | 2022 snapshot |
| --- | --- | --- |
| Q2A version recorded in the archive | 1.7.4 | 1.8.3 |
| Themes present | FlatBox, SnowFlat, Snow | FlatBox, SnowFlat, Snow, Candy, Classic |
| Language directories | Custom and British English | Custom, British English, and Turkish |
| Additional extension directories | Includes notifications, social sharing, a visual editor, and sitemap support | Also contains Markdown editing, Open Login, tag descriptions, and spam-prevention components |

These are differences between the filtered backups, not a complete changelog. Some components are third-party projects; their presence does not mean they were originally authored by W3Uzman. Features and installation have not yet been tested in these restored snapshots.
## Development highlights

W3Uzman grew from a Question2Answer installation into a customized community site through years of development. Its maintainer describes work across the public interface, administration, profiles, and advertising. The archive is being assembled to make this historical work available for study and future improvements.

The maintainer identifies the following areas of development:

- **Responsive layouts:** improvements for mobile phones and tablets.
- **Content presentation:** sliders, sidebars, and additional page elements.
- **Community experience:** expanded profile features and profile pages.
- **Administration and monetization:** changes to administration panels and advertising areas.

The source contains responsive styling and customized theme code. The September 2026 backup also includes source changes in page formatting, question rendering, widgets, and the FlatBox theme compared with the filtered 2022 import. Some theme and plugin images omitted from the earlier imports are present in the 2026 snapshot.

The items above describe the maintainer's development history; their exact scope, authorship, and behavior have not yet been independently verified across every archived snapshot. Database-driven settings and site content are not included in these source snapshots.
## Later development copy

The latest imported copy shows W3Uzman moving toward a news and content presentation while retaining its Question2Answer foundation. Its source includes a modern editor plugin, FlatBox theme customizations, responsive styles, and a collection of W3-prefixed modules for features such as domain and WHOIS lookup, DNS records, SEO checks, SSL checks, QR codes, image/PDF utilities, and text/JSON tools.

The maintainer describes extensive redesign and development of the FlatBox-based theme over the life of W3Uzman; the historical FlatBox name was retained. The source retains the original FlatBox copyright and GPL notices. The maintainer also describes work on richer sidebars, visual content areas, content creation, and an approach intended to reduce database growth from stored content. The exact behavior, dependencies, storage design, and completeness of these features still need code review and testing. Some modules can depend on external services or data files that are not included in the filtered archive.
### Web tools and services

The tools directory lists twelve services. The source for these modules is included in the latest snapshot; their runtime behavior and external dependencies still need verification.

1. **Domain name ideas** — generates domain name suggestions from a topic or keyword.
2. **Page SEO check** — examines page metadata and search/social tags.
3. **Image to PDF** — combines supported images into a PDF document.
4. **WHOIS lookup** — looks up available domain registration information.
5. **Domain lookup** — checks information about a domain name.
6. **DNS records** — looks up DNS records for a domain.
7. **SSL certificate check** — inspects SSL certificate details.
8. **What is my IP?** — displays information about the visitor's IP address.
9. **Responsive preview** — provides screen-size previews for a website.
10. **QR code generator** — creates QR codes for supported content types.
11. **Text utilities** — includes text cleanup and counting tools.
12. **JSON editor** — formats, validates, and compresses JSON.

### Content creation and community

- **Modern editor:** a Quill 2-based rich-text editor plugin is present in the latest source.
- **Visual editor:** the archive includes a CKEditor-based WYSIWYG editor plugin.
- **Markdown editor:** a separate Markdown editor plugin is included for text-based authoring.
- **Membership and profiles:** the maintainer describes improvements to membership, profiles, and user-facing pages across W3Uzman's development.
- **Modules and plugins:** the maintainer describes extensions to the modular architecture; the latest source includes W3-specific modules alongside third-party Q2A plugins.
- **News and visual presentation:** the later copy includes a customized FlatBox theme and news-oriented layouts; the maintainer describes further work on sidebars, sliders, and visual content areas.
- **Search visibility:** the maintainer describes broader site SEO improvements; the source also includes the separate SEO checking tool listed above.
- **Content storage:** the maintainer reports changes intended to reduce database growth. The precise implementation and its dependencies remain under review.

Editors and other bundled components may include third-party code. Their presence does not imply original authorship by W3Uzman. Features, integrations, and responsive behavior have not yet been tested end to end in this restored copy.
## Inside the archive

The September 2026 desktop snapshot includes an updated FlatBox theme source and restores theme images excluded from the earlier filtered imports. The later development copy contains additional W3-prefixed modules and theme assets.

The repository includes Q2A-based PHP source, language files, plugins, and themes, including FlatBox, SnowFlat, and Snow. The 2022 and 2026 snapshots also include Candy and Classic themes. The contents reflect the surviving backups and may not represent every feature of the historical site.

For privacy, actual configuration files, databases, uploads, backups, and unrelated test sites were excluded. Some assets and optional files were also left out during the initial review; the included themes and plugins may therefore be incomplete.

## Working with the archive

The snapshots are intended for code review and preservation while the repository is being prepared for public use. They should not be treated as production-ready packages: deployment instructions, a sanitized example configuration, dependencies, and restoration of any missing assets still need review. If you explore the code, follow the history links above to compare the available snapshots.
## License and attribution

The Question2Answer-derived code is distributed under [GPL-2.0 or later](https://www.question2answer.org/license.php); the GPL version 2 text is included in [COPYING](COPYING) and the original [license notice](LICENSE.html) is retained. Bundled themes, plugins, and libraries retain their own copyrights and license terms. For example, FlatBox and its companion plugin include GPL version 3 notices, while Quill and CKEditor carry their own license notices. Individual third-party components and their redistribution conditions are still being reviewed before the repository is made public.

---

Maintained by **W3Uzman** · Historical Q2A project archive