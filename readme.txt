Padlock WordPress Plugin
.............................................
This WordPress plugin restricts view access to posted content in a WordPress installation by allowing 
a site administrator (or super administrator in the case of a networked installation) to group users. 
Groups can then be allowed to have access to posted content in any of the site's categories.

Enjoy!


Features
.............................................
Site users can be organised in groups.
Built-in categories and custom taxonomies are supported.


Installation
.............................................
Place the padlock folder in the WordPress plugin directory. This is normally in /wp-content/plugin
Log in as Administrator and activate the plugin from the admin plugin page


Configuration
.............................................
Go to the Users > Groups admin page and create one or more user groups (keep the group names short)
Assign users to any of your groups by ticking the check boxes on the grid and then clicking the Save button. 
Users with admin rights can not be limited so all their boxes are permanently ticked.
Go to the Settings > Access admin page
Specify the theme template your users will see instead of restricted content
Tick on the checkboxes in the grid to allow group access to post categories and click Save.


Permission rules
.............................................
When a user has access to category Blue, but not to Red and an administrator places an article 
in both Blue and Red, the assumed intention would be that the administrator wants people with 
access to Blue to see the content as well as people with access to Red.
The permissions do not cascade down category hierarchies. 
The permissions on a parent category applies it's child articles, but not to it's child categories.


Release notes
.............................................
https://bitbucket.org/onesheep/padlock/wiki/Release%20notes


License
.............................................
Copyright 2013 OneSheep (http://onesheep.org)

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, 
version 2, as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details.