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

## DDEV local

To use the included DDEV local configuration, run

* `ddev start` from the extensions root directory to start the container
* `ddev config` to create required configuration files if not yet present
* `ddev launch typo3` to get to the TYPO3 backend directly

If you are setting up the environment for the first time, create a file named `FIRST_INSTALL` in `.build/web/` and
proceed with the TYPO3 installation as described in the [official documentation](https://docs.typo3.org/m/typo3/guide-installation/master/en-us/QuickInstall/TheInstallTool/Index.html#the-install-tool).

## Credits

This extension was created by [Andreas Beutel](https://github.com/abeutel) in 2019 for
[mehrwert intermediale kommunikation GmbH](https://www.mehrwert.de).

Thanks to all contributors and everybody providing feedback.
