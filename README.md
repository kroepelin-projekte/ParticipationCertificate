# ParticipationCertificate

This plugin adds the user interface for the `ParticipationCertificate` plugin:
* Adds a new tab 'Certificate' for printing Certificates


## Installation
Start at your ILIAS root directory
```
mkdir -p public/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
cd public/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
git clone https://git.studer-raimann.ch/ILIAS-Kunde-DHBW-Karlsruhe/ParticipationCertificate.git 
```
As an ILIAS administrator, go to "Administration -> Plugins" and install/activate the plugin.

## Update
After updating version 1.2.0, a function is executed that scans all files and directories within the certifications path. It then inserts relevant data into the dhbw_part_cert_files table.

This table is used to track whether a logo and signature already exist for a certificate. If they do, the existing file is retrieved using:

```
$src = $DIC->fileDelivery()->buildTokenURL(
$stream,
$fileName,
\ILIAS\FileDelivery\Delivery\Disposition::INLINE,
$DIC->user()->getId(),
6
);
```

If the logo or signature does not already exist in the table, the system falls back to retrieving the resource via:


```
$src = $DIC->resourceStorage()->consume()
->src($resource)
->getSrc();
```

In this case, the file is assumed to have been imported using Kitchen Sink.

## Testing Image Uploads in Docker (Development Environment)

To enable testing of image uploads in a Docker-based development environment, you should modify the `generatePDF()` function in the `./classes/Report/ilParticipationCertificatePDFGenerator.php` file.

Add the following line:

```
$rendered = str_replace('http://localhost:<port>', 'http://host.docker.internal:<port>', $rendered);
```

This ensures that image URLs referencing localhost are correctly resolved from within the Docker container by redirecting them to host.docker.internal.

Replace <port> with the actual port number your application is running on.

## Dependencies

Plugin LearningObjectiveSuggestions must be installed and activated