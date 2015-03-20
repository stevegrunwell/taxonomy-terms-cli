# Taxonomy Terms CLI

A [WP-CLI](http://wp-cli.org/) command to retrieve information about taxonomy terms in your WordPress installation.


## Installation

The command doubles as a WordPress plugin, so installing it is as simple as putting this repo in your WordPress plugins directory and activating the plugin. You may also include the taxonomy-terms-cli.php file directly from your theme or another plugin.

For instructions on installing WP-CLI, please [see that project's website](http://wp-cli.org/).


## Basic usage

To retrieve a simple list of all taxonomy terms in your WordPress instance, run the following command from the command line:

```bash
$ wp taxonomy-terms list
```

WP-CLI will print a nicely-formatted table, that will look something like this:

```bash
+----+----------+---------------+---------------+---------+
| ID | Taxonomy | Name          | Slug          | # Posts |
+----+----------+---------------+---------------+---------+
| 1  | category | Uncategorized | uncategorized | 1       |
+----+----------+---------------+---------------+---------+

Success: One term found.
```


### Saving results to a CSV

Fortunately, the [PHP CLI Tools library](https://github.com/wp-cli/php-cli-tools) maintained by the WP-CLI team (and is used for the table output in this plugin) automatically detects if a table output is being [piped](http://ryanstutorials.net/linuxtutorial/piping.php) to another program and, if so, the table is rendered as tab-separated rows instead.

To save a list of taxonomy terms to file, simply do the following:

```bash
$ wp taxonomy-terms list > my-taxonomy-terms-file.csv
```


## Options

There are a few different options available when running the command, most of which correspond to the [`get_terms()` WordPress function](http://codex.wordpress.org/Function_Reference/get_terms), which is what does the heavy lifting in this command.


### --order

The direction to order results, either "asc" (ascending) or "desc" (descending). Default is "asc".


### --orderby

The field value to order results by, and should be used in conjunction with `--order`. Options include:

* **id:** The term ID
* **count:** The number of posts associated with this term.
* **name:** Alphabetically by the term name.
* **slug:** Alphabetically by the term slug.

By default, results are ordered by "name".


### --taxonomy

Separate one or more taxonomies to limit results to. Separate multiple taxonomies with a comma.

By default, [all public taxonomies](https://codex.wordpress.org/Function_Reference/register_taxonomy) will be included.

#### Example

The following will only return terms in the "post_tag" taxonomy:

```bash
$ wp taxonomy-terms list --taxonomy=post_tag
```


## License

Copyright (c) 2015 Steve Grunwell

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.