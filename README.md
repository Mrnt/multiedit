MultiEdit Plugin
=========

Enables multiple editable content blocks on page templates.

Derived from <a href="http://wordpress.org/extend/plugins/pagely-multiedit/">Page.ly MultiEdit</a> by joshua.strebel

Installation
--------------

1. Download the zip
2. Unpack and upload to the /wp-content/plugins/ folder
3. Activate the plugin


How to use
--------------

1. Create a custom page template or modify an existing page template in your theme and declare MultiEdit regions as a comma separated list under Template Name. NOTE: Do not add spaces the list or region names:

        <?php
        /* 
        Template Name: My Custom Template 
        MultiEdit: Right,Bottom,Left 
        */ 
        ?>

2. Inside the template call <i>multieditDisplay('region')</i> to display the content, eg:

        <div id="some-section">
             <?php multieditDisplay('Left');?>
        </div>

        <div id="some-other-section">
             <?php multieditDisplay('Right');?>
        </div>

        <div id="yet-another-section">
            <?php multieditDisplay('Bottom');?>
        </div>

3. Create a page or change an existing page to use the template, which in this case would be called <i>My Custom Template</i>.

4. Hit the Update button to save the template choice and reload the page. There should now be a tab above the Wysiwyg editor for each of the regions defined in the template.