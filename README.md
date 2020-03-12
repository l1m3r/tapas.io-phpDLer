# tapas.io WebComic Downloader
Unofficial downloader for webcomics on https://Tapas.io/ written in PHP.

### Features:
 * automatic resume. 
 * filenames of saved images/objects contain the episodes name.
 * compatible with episodes consisting of more then one image/object.
 * doesn't stop at episodes marked as mature but saves URLs to them instead.

Saved images get filenames like this: `<mainCounter>-<episode#> <episodeTitle>.<ext>`

The [original author](https://github.com/TilCreator/) of the [ancestor of this script](https://github.com/l1m3r/tapas.io-phpDLer/commit/7e54ee2) has written a [Python alternative](https://github.com/TilCreator/Tapas-Comic-Downloader).

## Prerequisites:
The only requirement is a compatible working php interpreter.

I'm using `php-7.1.8-Win32-VC14-x64.zip`.

Let me know if it works on other OSs.

## Usage:
1. Get episode number to start from.
 * Go to the comics page from where you want to start downloading (usually the first).
 * Copy the integer from that pages URL.
 * Examples: `https://tapas.io/episode/255222`  ->  `255222`
2. Start the download.
 * Usage of `tapas.io_downloader.php`:
 ```
 tapas.io_downloader.php -e <EP#> -p <path>
 ```
 * Windows example (can be saved in a shortcut/.lnk too):
 ```
 C:\php\php.exe tapas.io_downloader.php -e 255222 -p "D:\Web Comics\Erma"
 ```
 * The script will create the folder(s) if necessary and store/save all images/objects of the comic in it.
 * If the folder already exists, it will resume downloading with/after the last saved episode.

## Missing features:
 * download episodes marked as mature too.
 * add optional argument to put a comic specific identifier at the beginning of all filenames.
 * or generate that preamble from the comic name?
 * give different return value depending on if any (and how many) images/objects were downloaded.
