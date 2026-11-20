# Author Column

[![Tests](https://github.com/phpbbmodders/authorcolumn/actions/workflows/tests.yml/badge.svg)](https://github.com/phpbbmodders/authorcolumn/actions/workflows/tests.yml) [![Lint](https://github.com/phpbbmodders/authorcolumn/actions/workflows/lint.yml/badge.svg)](https://github.com/phpbbmodders/authorcolumn/actions/workflows/lint.yml)

Adds an Author column to forum topic lists and topic search results in prosilver and its child styles.

## Features

- An **Author** column between the topic title and **Replies**, showing who started each topic, in their group colour.
- Shown in forum topic lists (announcements and normal topics) and in topic search results, including **Active topics**, **Unanswered topics** and searches by author. Post-mode search results and the forum list are unchanged.
- Hidden on screens 950px wide or narrower, where the topic titles need the room; the "by author" line under each title still shows there.
- Usernames too long for the column end in "…". The column is 150px wide on phpBB 3.3 and 190px on phpBB 4.0, whose topic list uses larger text. To change it, set `--authorcolumn-width` in your style's stylesheet.
- Works with right-to-left languages.
- No settings: enable the extension and the column appears.

## How it works

phpBB has no template event inside the topic list's heading row, or between the topic and **Replies** cells, so an extension can't add a column there with event templates. Author Column ships its own copies of prosilver's `viewforum_body.html` and `search_results.html`, one set each for phpBB 3.3 and 4.0, and uses them in place of prosilver's.

- Only prosilver and styles based on it use the copies. Other styles keep their own pages.
- A child style that ships its own `viewforum_body.html` or `search_results.html` keeps that page.
- Every template event in prosilver's pages is still in the copies, so other extensions that add to the topic list keep working.

The copies are generated from phpBB's source by [`tools/build_templates.py`](tools/build_templates.py). When prosilver's templates change in a phpBB release, rebuild them, for example:

```bash
python3 tools/build_templates.py -p ~/phpbb -3 release-3.3.19 -4 origin/master
```

## Requirements

- phpBB 3.3.19 or later, including phpBB 4.0 (tested on its 4.0.0-a3 development version)
- PHP 8.1 or later (phpBB 4.0 itself needs PHP 8.2 or later)
- prosilver, or a style based on prosilver

## Installation

1. Copy the extension to `/ext/phpbbmodders/authorcolumn`
2. In the Administration Control Panel, go to **Customise → Manage extensions**
3. Enable the **Author Column** extension

## Contributing

Contributions are welcome!

- **Bug reports**: [Open an issue](https://github.com/phpbbmodders/authorcolumn/issues).
- **Everything else** (questions, feature requests, ideas, general discussion): [Use Discussions](https://github.com/orgs/phpbbmodders/discussions), or the [community forum](https://www.phpbbmodders.com/community/).
- Pull requests are welcome for bug fixes or discussed features.

## Acknowledgments

- Based on Allach's request in the prosilver Special Edition support forum on phpBB.com: [add author column](https://www.phpbb.com/customise/db/style/prosilver_special_edition/support/topic/258055).
- The template copies are prosilver's own, from phpBB by phpBB Limited, licensed under the GNU General Public License v2.0.
- Code, tests and documentation written with [Claude](https://www.anthropic.com/claude).

## License

This extension is licensed under the **GNU General Public License v2.0**.

See [license.txt](license.txt) for more information.
