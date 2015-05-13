## Joomla Plugin Global Variables  (plg-globalvariables)

<img src="https://raw.githubusercontent.com/echterhoff/plg-globalvariables/assets/icon.png" width="150" align="right">
### Latest changes
 Complete rewritten from scratch.
 ...and since this is a complete rewrite I have cut compatibility and reduced the variable syntax to {{varname}} and {global}varname{/global} only. Please dont complain about that.

### Global Variables for Joomla! 3.4+
This plugin parses the content on prepare time for variables within the content elements and replaces the variables with defined values. I wrote this plugin just because I did not find a plugin that could serve me with a easy solution like this...

If you ever messed around with information fragments like phone number, vat number or kind of information that is used in more than one place you gonna love this plugin. Instead of crawling the page for each occurance it is way more convenient to change this information just in one place.

This new version offers much more control over every aspect of variable handling and variable sources. Valid variable sources are articles, files and scripts as well as an internal variable management.

Defining a variable file is as easy as writing an ini-File. Specify a variable name followed by a equal sign (=). Type in your variable content and end the definition with a semicolon (;). If you like to define a more complex content, surround the value with (").

You dont need to close your definition by a semicolon. Just quote the variable content with ' or "

To use a variable, just place the variable name in your article as {{varname}}. As an alternative wrap your variable name with {global}varname{/global}.

This is the basic usage.

Since this is a all new version, there are several improvement like:
 - Support for multilanguage use. {{varname lang=de-DE}} or {{varname.de-DE}} or simply {{varname}} and let Falang handle it. (Falang compatibility)
 - Source selection beside the selected default source. {{varname source=myname}}
 - Use files from Dropbox, your public ftp or what ever is accessible from your web server as source.
 - Script request. {{varname query=1}}
 - Debug modes: Highlight or expose the tags in your page.

### Installation

Download the plugin from here. https://github.com/echterhoff/plg-globalvariables/archive/master.zip
 1. Go to your Joomla! administration interface
 2. Install the .zip file with the installer
 3. Go to your plugin and open Global Variables from the plugin browser
 4. Switch it on by activating it.
 5. Open the plugin configuration

### Use

Define some variables and place them within your articles or modules

Define variables like:

```
myvariable="this is my var";
```

Use them within your content with:

```html
<p>Blahblah</p>
<p>This is my article content and {global}myvariable{/global}!</p>
```

This will result in:

This is my article content and this is my var!

I hope you will find this tool as useful as me. If you got questions or problems, dont hesitate to contact me.

Cheers Lars


[logo]: https://raw.githubusercontent.com/echterhoff/plg-globalvariables/assets/icon.png "Joomla! Plugin - Global Variables"
