# Changelog

To get the version for your current 1100CC installation, view `./APP/CORE/CMS/info/version.txt`

## VERSION 7.1

Initial release.

## VERSION 7.2

* Model: Fixed an error when toggling for default value in data_model.php.
* Visualisation: Improved user interaction to use weighted data in the visualisations based on Conditions and MapGeo/MapSocial/MapTimeline.js
* Analysis: Renamed Analysis metric 'Count' to 'Degree Centrality' and added the weight option.
* Visualisation: Fixed/improved the combination of touch an mouse interaction in MapScroller.js and MapSocial.js.
* Import: Added the option to limit the amount of rows from one CSV file when importing data. This can be adjusted in the CMS under nodegoat Details.

## VERSION 7.3

* All: Introducing a statement-based/chronology dating system that positions an Object in time. Statements can be uncertain/cyclical/relational and are translated using 'ChronoJSON' (https://twitter.com/nodegoat/status/1156550866188546049).
* Filter: Added a functionality to date-related filtering to find Objects with events before or after related other events; combine Filter and Scope for value-based deep filtering.
* Data/Analysis: New class CollectTypeObjecsValues.php extends CollectTypeObjects.php and allows you to efficiently collect values down an Object's relational path.
* Model: Extended the functionality to link/lock the date start and date end of a Sub-Object to separate other (Sub-)Object Descriptions.
* Data: Moved all Object caching/processing logic to their own class in StoreTypeObjectsProcessing.php.
* Data: Added date/chronology caching next to the already present location/geometry cache.
* Project/Data: Added the option to add descriptive information on hover/click to Types and their Descriptions.
* Model: Added the option to change the separation character for multi-value Descriptions.
* Model: Added the option to specify whether media files should be displayed or only be shown as links.
* Import: Added the option to choose whether to overwrite or append values.
* Import: Added a functionality to generate logs when running Import Templates that stores decisions made or possible errors.
* Import: Added a functionality to ingest CSV files containing multi-line values.
* Conditions: Added support for regular expressions.
* Data: Added the option to change and store the order of multi-value Descriptions.
* Model: Added the option to set a specific minimal clearance for users to be able to add/edit Objects in a Type.
* Project: Added the option to state (true/false) whether users can add/edit Objects to a certain Type in a Project.
* Data: Fixed uploading additional media when a Object Description with a media type is set to multiple in StoreTypeObjects.php.
* Filter: Extended the addition/reduction mode to indicate whether a filter form should reduce the Object result set but expand the result set for Sub-Objects of the same kind.
* Filter/Data: Results of an active Filter are now cached client-side. This allows for faster subsequent interaction, e.g. quicksearch and pagination.

## VERSION x.x
