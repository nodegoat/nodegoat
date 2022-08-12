# Changelog

To get the version for your current 1100CC installation, view `./APP/CORE/CMS/info/version.txt`

## VERSION 7.1

Initial release.

## VERSION 7.2

* Model: Fixed an error when toggling for default value.
* Visualisation: Improved user interaction to use weighted data in the visualisations based on Conditions.
* Analysis: Renamed Analysis metric 'Count' to 'Degree Centrality' and added the weight option.
* Visualisation: Fixed/improved the combination of touch and mouse interaction in MapScroller (1100CC) and MapSocial.
* Import: Added the option to limit the amount of rows from one CSV file when importing data. This can be adjusted in the CMS under nodegoat Details.

## VERSION 7.3

* All: Introducing a statement-based/chronology dating system that positions an Object in time. Statements can be uncertain/cyclical/relational and are translated using 'ChronoJSON' (https://twitter.com/nodegoat/status/1156550866188546049).
* Filter: Added a functionality to date-related filtering to find Objects with events before or after related other events; combine Filter and Scope for value-based deep filtering.
* Data/Analysis: New class CollectTypeObjecsValues extends CollectTypeObjects and allows you to efficiently collect values down an Object's relational path.
* Model: Extended the functionality to link/lock the date start and date end of a Sub-Object to separate other (Sub-)Object Descriptions.
* Data: Moved all Object caching/processing logic to their own class in StoreTypeObjectsProcessing.
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
* Data: Fixed uploading additional media when a Object Description with a media type is set to multiple.
* Filter: Extended the addition/reduction mode to indicate whether a filter form should reduce the Object result set but expand the result set for Sub-Objects of the same kind.
* Filter/Data: Results of an active Filter are now cached client-side. This allows for faster subsequent interaction, e.g. quicksearch and pagination.

## VERSION 8.0

* Visualisation geographical: Sub-Object connection paths can now make use (trace) of a Sub-Object's geometry when the geometry contains a LineString.
* Visualisation geographical: Added the possibility to render paths and geometries across map boundaries. This also means connection paths are now generated based on their shortest distance and could therefore cross the map boundaries.
* Visualisation geographical: The center of the map is now rendered based on a given latitude and longitude which can be set using a visualisation Frame.
* Export: Added the rich-text format OpenDocument Text (.odt).
* Export: Added the option to choose whether to export data for each Object on a separate page, or apply a continuous flow. Applies to the export format OpenDocument Text (.odt).
* Model: Introducing system-defined Types, extends the existing Type infrastucture.
* Model: Migrated and streamlined all Reversal and Chronology Statement Cycle logic to the new system-defined Type infrastucture.
* Model: Added a new Object Description value type 'Application', internally called 'module'. This value type allows Objects to interact, communicate, and store data using various advanced and extendable functionalities. Classes can be created (drop-in / plugin) that add possible applications.
* Model: Added a new Object Description value type 'Serial String'. This value type allows Objects to store auto-incrementable integers as part of an optional string.
* Model: Added a new Object Description value type 'Numeric' to host both whole nubmers and decimals. Renamed the 'Integer' value type to 'Number'.
* Ingest: Reworked ImportTypeObjects into a generic IngestTypeObjects class and ingest_source module that can be extended by both data_import and data_ingest. 
* Ingest: Created the module data_ingest and a new system-defined Type 'Ingest' that use Linked Data Resources to dynamically ingest external data into a data model.
* Linked Data: Extended ResourceExternal to be able to communicate with any proper API or SPARQL endpoint that is able to output JSON. Using URI templates it is now possible to store and interchangeably use full or partial URIs (i.e. only the identifier part).
* Information Retrieval: Improved and extended the information retrieval service to be fully in sync with the master SQL database.
* Model/Filter: Added routines to touch an Object's status upon deletion creating the possibility to query for deleted Objects and its related Types.
* Data: Renamed tabs in the Type overview from Types/Classifications/Reversals to Objects/Categories/Processes to better accommodate semantics and the new system-defined Types.
* Data: Added a copy button in the Sub-Object editor to quickly duplicate and edit Sub-Objects.
* Data: Added a 'reference' option to the possible Sub-Object date options to be able to shortcut a Chronology Statement with a reference.
* Data: Added the possibility to multi-change a Sub-Object's start and end date independently.
* Visualisation: Created new routines to render and capture visualisations as configurable high-resolution images using 1100CC's new CaptureElementImage.
* Visualisation social: Improved the WebGL shaders and performance.
* Visualisation social: Added the possibility to render layouts using different algorithms.
* Visualisation timeline: Added new charting modes to the Chronological visualisation.
* Scenario: Implemented and extended Scenario caching for filters and visualisation, enabling instant interactions on elaborate Scenarios.
* Frame: Extended the zoom configuration to be able to set the available zoom levels for each visualisation mode.
* Data: Added a new multi-change mode 'replace' to support value replacements using simple match/replace and advanced regular expressions.
* Visualisation geographical: Developed and implemented new modes for location label placement. Labels can be positioned algorithmically (applies force and collision) to prevent overlap in locations and their labels, and labels can be dragged manually. 
* Project: Added a Project overview mode that generates a visualisation for the graph of the implemented data model and its options. The graph can be rearranged by dragging, and highlighted by clicking.
* Model: Created a value type Application extention 'Music Notation' to be able to store and render sheet music. The rendering to SVG is done by Verovio (verovio.org) as a WebAssembly file.
* Pattern Pairs: Moved and extended the 'String To Object Pairs' functionality from Import to its own separate new StorePatternsTypeObjectPair class and data_pattern_pairs interface. Pattern Pairs can communicate with current (Import/Ingest and upcomming Reconcile) and future functionalities.
* Various fixes, modernisation, and overall streamlining.

## VERSION x.x
