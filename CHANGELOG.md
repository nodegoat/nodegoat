# Changelog

Go to [nodegoat Release](http://nodegoat.net/release) to view the pre-release/development version of the changelog.

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
* Model: Added a new Object Description value type 'Numeric' to host both whole numbers and decimals. Renamed the 'Integer' value type to 'Number'.
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
* Data/Model: Geometries now support and enforce their geographic coordinate system (indicated by their CRS in e.g. GeoJSON). By default geometries are interpreted using the WGS84 geographic coordinate system (EPSG:4326). Any other geographic coordinate system in the EPSG registry is now supported with help of the GDAL library (gdal.org), and can be used interchangeably throughout.
* Various fixes, modernisation, and overall streamlining.

## VERSION 8.1

* Data/Toolbar: Added new Objects selection layer to the existing Filter interaction layer. Manually selecting or deselecting Type Objects narrows or reversibly expands the results of a possible active Filter.
* Analysis: Implemented Conditions to source the weights used in a graph. Sourcing weights from Conditions allows for any level of constraint or assertion, ranging from the whole graph to the level of a single edge.
* Filter: Revamped the implementation of the (path-aware) filtering procedures providing performance, consistence, and scalability.
* Various fixes and tweaks.

## VERSION 8.2

* Reconcile: New class ReconcileTypeObjectsValues is able to test and score any textual source with any Type and its Objects.
* Reconcile: Created the module data_reconcile and a new system-defined Type 'Reconcile' to map, reconcile, and store text into references. This class also supports tagging matched Objects in the source text.
* API: Added the required 'Reconciliation Service API' standards to make the new reconciliation functionalities available through the API.
* Data: Expanded the data entry version history interface for improved interaction when inspecting or using previous version states.
* Scope: Changed the Scope interface in the module data_model from expanded horizontal connection selection to vertical drop-down connection selection. This allows for a more directed and cleaner navigation. The horizontal navigation remains available.
* Scope: Implemented temporally-aware dynamic graph traversal. This allows for the option to traverse graphs while applying and passing temporality to subsequent time-bound connections in an Object's path. The Scope's interface module in data_model and the collectTypeObjects class have been expanded to support the configuration and evaluation of dynamic path connections.
* Import: Added the ability to update specific Sub-Objects by linking sources to Sub-Object IDs in addition to the nodegoat ID.
* Various fixes and tweaks.

## VERSION 8.3

* Visualisation social: Extended the label settings for the social visualisation. It is now possible to control the threshold and the Condition when to show or hide node labels.
* Reversals: Added an extension to the Reversal mode 'Reversed Collection' by allowing the configuration of templates using 1100CC's TraverseJSON Paths. The Resource Paths can traverse the Scope and store the dynamic values (i.e. can contain both text and references) it encounters.
* Reversals: Moved setReversals to its own class. Now the class StoreTypeObjectsReversals manages all Reversal-related processes and the class StoreTypeObjectReversalCategoryReferencedType applies the specific Reversal configurations. The new queuing logic supports iterating over self- and cross-referencing Reversals, and only runs a Reversal with its Categories when changes in any of its (networked) related data should be considered.
* Publication: Added modules and classes to be able to publish a Project's data model and all of its data. Publications are fully stand-alone archives which include both the HTML-interface to the data model as well as all of its data in both JSON and CSV. The module publish_instances and the class StorePublishInstances provide the interface to manage Publications and their versions. The module 'publish' provides access the the published Projects that are generated by the class PublishInstanceProject.
* Data: Added the option for Projects to set Filters on its included Object and Sub-Object Descriptions. These Filters will limit the available references during data entry.
* Model: Moved all Type Object format/value logic to its own class FormatTypeObjectsBase which can be fully extended for e.g. custom tweaks.
* Public User Interface: Streamlined options and settings in the modules ui, ui_data, and public_interfaces.
* Various fixes and tweaks.

## VERSION x.x
