.. include:: ../Includes.txt


.. _knownproblems:

==============
Known Problems
==============

* Quota limits are stored in Bytes internally and transformed to MB through TCA evaluation. Currently they will not be
  properly re-calculated when copying storages.

.. tip::

   In case you find other issues or want to suggest features or enhancements, just add your report or idea to the issue
   tracker on `GitHub <https://github.com/mehrwert/TYPO3-FAL-Quota/issues>`__. Contributions are welcome :-D

Supported Platforms
===================

FAL Quota uses TYPO3 standard Hooks and Singals/Slots to do the checks and thus should run on all supported platforms
with storages using a `Local` driver. However it has not yet been tested on Windows installations where the
`disk_free_space()` method could have issues. Feedback welcome!
