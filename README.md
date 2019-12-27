# FAL Quota for TYPO3

This extensions provides a virtual Quota for FAL using Signals/Slots if the underlying file system does not support
or cannot provide one. A CLI command updates the quota usage periodically and sends notification mails to recipients
defined per storage. 

## Features
* Per storage definition of soft quota and hard limit, notification threshold and email recipients 
* Symfony Command task to update quotas and send notification mails

## Installation

After installation, you may optionally create a scheduler task for notifications.

### Installation using Composer

The recommended way to install FAL Quota is by using [Composer](https://getcomposer.org):

    composer require mehrwert/fal-quota

### Installation as extension from TYPO3 Extension Repository (TER)

Download and install the extension with the *Extensions* module.

## Submit bug reports or feature requests

Look at the [Issues](https://github.com/mehrwert/TYPO3-FAL-Quota/issues)
for what has been planned to be implemented in the (near) future.

## Credits
Thanks to all contributors and everybody providing feedback.
