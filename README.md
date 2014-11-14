plg-globalvariables
===================

Joomla Plugin GlobalVariables (plg-globalvariables)


Global Variables
for Joomla! 3.3
This plugin parses the content on prepare time for variables within the content elements and replaces the variables with defined values. I just wrote this plugin just because I did not find a plugin that could serve me with a easy solution like this... Hell it is way to easy but nobody out there came up with a solution like this?? So here we go.

You know the problem. You have informations like a telephone number, vat number or kind of information that is used in more than one place withing you joomla site. Instead of crawling the page for each occurance of this value it is way more convinient to change this information just in one place. So, dont look further, this plugin is the answer. Just create a variable in the plugin interface and place it within your content. The plugin does the rest.

Defining variables is quite simple. I assume you found this plugin while searching for globals or variables or kind of. So defining variables is as easy as writing an ini-File. Give a variable followed by a equal sign (=). Close the definition with a semicolon (;). If you would like to define a more complex value, surround the value with (").

Since version 1.1 you may define an article at the plugin settings page that keeps your variables. In this case the syntax is slightly different.

You dont need to close your definition by a semicolon. Just quote the variable content with ' or "

To place a variable, you might want to use the more common curly bracket syntax. Just wrap your variable name with {global}{/global}.

In a nut shell

Download the plugin from here. http://code.google.com/p/plg-globalvariables/downloads/detail?name=plg_globalvariables_j2.5_v1.1.zip
Go to your Joomla! administration interface
Install the .zip file with the installer
Go to your plugin and open Global Variables from the plugin browser
Switch it on by activating it.
Define some variables and place them within your articles or modules
Define variables like:

myvariable="this is my var";
since v1.1

myvariable="this is my var"
Use them within your content with:

This is my article content and var_myvariable()!

since v1.1: {global}myvariable{/global}

This will result in:

This is my article content and this is my var!

I hope you will find this tool as useful as me. If you got questions or problems, dont hesitate to use the bug tracker or just contact me.

Regards Lars