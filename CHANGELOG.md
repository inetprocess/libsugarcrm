Changelog
=========
1.2.1
-----
* Added "update_date_modified" in bean in case we want to force the date_modified

1.2.0
-----
* Out of beta \o/
* Install performs offline without a web server

1.1.22-beta
-----
* Fix warning and output on System::repair

1.1.21-beta
-----
* Add more functions for specific Repair and Rebuild
* Tests improved

1.1.20-beta
-----
* Fix tests for Repair and Rebuild

1.1.19-beta
-----
* Fix issue where email address would not be retrieved in `UsersManager`.

1.1.14-beta -> 1.1.18-beta
-----
* Button with capitalized letters was not found in MetadataParser
* Some hardcoded fields in SugarCRM were not detectable in vardefs
* If the entrypoint is loaded again with the same path, now return the same instance
* Add deleteBean + corrected entrypoint to reload the current user
* Remove useless constant and load current user when get entry point
* Lot of new Unit Tests
* Corrected sugarPath
* Invalid attribute makes a change in bean
* Moved method updateBeanFieldsFromCurrentUser in Bean.php
* Corrected a bug with searchBeans and getList that doesn't retrieve more than 40 records
* Added Relationship Diff
* Added exceptions when we can't retrieve the module table


1.1.13-beta
-----
* New class MetadataParser to add / remove buttons via SugarCRM Modulebuilder parser
* New Util to remove a Label

1.1.12-beta
-----
* New methods in System to Disable Activities (speeds up some tasks)

1.1.11-beta
-----
* Change the license from GPL to Apache 2.0

1.1.10-beta
-----
* Bean.php: Utility function to convert to array values.

1.1.9-beta
-----
* LangFileCleaner: Merge global and local version of variables.
* Quick Repair: Fix issue with Sugar7 record.php files not cleared.

1.0.0
-----
* Add UsersManager class.
* Reworked Bean::updateBean

0.9.1
-----
* Protect column identifiers in INSERT and UPDATE queries with QueryFactory

0.9.0
-----
* Add LangFileCleaner class to sort and clean SugarCRM language files and make it easier for VCS
* Add Installer class to extract and install sugar from a zip file.
* Add SugarPDO to connect to sugarcrm database with php PDO
* Add Metadata class to manage `field_meta_data` table.

