# Screenplay File Parser/Extractor

* Author: **Alex Coppen** (azcoppen@protonmail.com)

## Important: Look at ScreenJSON instead

This package was a quick hack to get at the data inside Final Draft (XML), Celtx (RDF), Adobe Story (XML), Fountain (Markdown) and FadeIn (XML) files. The long-term future can't be about spaghetti-coding this stuff to deal with a dozen different proprietary file formats that are useless for doing anything meaningful. ScreenJSON is an effort to get **rid** of the writing packages/modules like this so there's a universal interchange format that can be imported into any programming language or platform, and can also be mined for information. Instead of wasting time dealing with these formats, come work on a better one!

## Overview

Once a script has been drafted by a screenwriter and financially *green-lit* for production, it is distributed to over 20 different film departments for *marking up*. Directors re-write, producers annotate, while wardrobe, locations, effects, casting and others, need to produce a **breakdown**.

That means manually compiling a list of scenes, characters, locations, and props, that will be required - which takes DAYS, and is pointlessly inefficient and archaic, when any computer can parse the information in seconds.

This library does one thing very simply: parse different types of script files to compile initial lists of scenes, characters, and props. Lists that can be copied and pasted into a breakdown very easily, to save time and effort.


## Install

Use composer to add the package to your project `vendor` folder.

```bash
composer require azcoppen/screenplay-parser
```

Or add to the `require` section of composer.json:

```json
    "require": {
      "azcoppen/screenplay-parser": "*",
    }
```

## Usage
All the files are entered into the `\Screenplay` namespace.

##### Parse an FDX file for characters, scenes, and capitalized action elements:

```php
use Screenplay\Extractor;

  try {

    $extractor = new Extractor( 'test.fdx' ); // format auto-detected from file ext

    $scenes 	= $extractor->analyzer()->parse_scenes();
    $chars 		= $extractor->analyzer()->parse_characters();
    $elements   = $extractor->analyzer()->parse_capitalized();

    print_r(json_encode($scenes, JSON_PRETTY_PRINT));
    print_r(json_encode($chars, JSON_PRETTY_PRINT));
    print_r(json_encode($elements, JSON_PRETTY_PRINT));

  } catch (\Exception $e) {
    var_dump( $e->getMessage() );
  }

```


## Supported Formats

##### Final Draft Pro 8/9/10 (XML)

Final Draft is the industry standard software for screenwriting - for little discernible reason. Version 7 produced proprietary binary files with the **.fdr** extension. Version 8 introduced the more helpful (although bloated) **.fdx** file, structured as XML.

Simple to traverse, using `simplexml_load_file` or `DomDocument`.

```xml
    <Paragraph Type="Action">
      <Text>Wheat fields. Etched in darkness and moonlight. For the moment it’s calm.</Text>
    </Paragraph>
    <Paragraph Type="Action">
      <Text AdornmentStyle="0" Background="#FFFFFFFFFFFF" Color="#000000000000" Font="Courier Final Draft" RevisionID="0" Size="12" Style="Bold">TITLE: Nebraska, 1875.</Text>
    </Paragraph>
    <Paragraph Type="Action">
      <Text>A WOMAN CRIES OUT, drawing our attention to a scant farm house in the distance.</Text>
    </Paragraph>
```

More:
* http://kb.finaldraft.com/article/1001/325/

##### Adobe Story (XML)

Adobe's web & desktop screenwriting software, **Story** (https://story.adobe.com/), as part of the Creative Cloud platform, is one of the newest entrants to the market and easily the most sophisticated. As part of a larger production management system, all files are meant to inter-operate with Digital Asset Management (DAM) and film production breakdown/scheduling documents. The native Adobe Story format (ASTX) is again just simple XML which is easily traversable using `simplexml_load_file` or `DomDocument`.

*Note: Story appears to have now migrated to using .stdoc files which are in a proprietary format.*

More:
* https://story.adobe.com/
* https://helpx.adobe.com/story/help/getting-started-story.html

##### Celtx (RDF)

Celtx was the first open-source platform for screenwriting that became a real competitor to Final Draft, also offering a full-production platform for independent filmmakers. A **.celtx** file is just a zip archive, like a .jar, .war, or .docx.The structure of the archive is as follows:

```
.
├── local.rdf
├── project.rdf
├── scratch-YpE.html
├── script-YpE.html
```

Resource Description Framework (RSS/RDF) files are simple to parse using `DomDocument`.

```xml
<p id="oHGP7x10" class="sceneheading">Ext. WOODS - NOON.<br></p>
<p class="action">ALEX, <span ref="http://celtx.com/res/ywj489TD9hGB"
 class="cast">JOSH</span>, GIRL 1, and GIRL 2, <span
 ref="http://celtx.com/res/I6Dr5rQJX4p4" class="cast"></span>are walking on a
trail to their campsite (which evidently is very far away). <br>
</p>
<p class="character">GIRL 2<br></p>
<p class="dialog">Are we almost there?<br></p>
<p class="character">GIRL 1<br></p>
<p class="dialog">Yea we've been walking forever.</p>
```


More:
* https://support.celtx.com/hc/en-us

##### Open Screenplay Format (XML)

Possibly the newest entrant to the format market is the XML format OSF, used by Fade In Pro (http://www.fadeinpro.com/). Open Screenplay Format is a small open-source project on SourceForge which doesn't appear to be particularly well documented, but is elegantly structured.

*Note: FadeIn Pro is exceptionally helpful for converting between formats.*

Once again, OSF is easy to parse using `simplexml_load_string`.

```xml
    <para synopsis="Calm fields of wheat. Farmland. The heart of America.&#xA;In darkness.&#xA;&#xA;Set location and time." synopsis_color="#40A0FF" note="Near Plattsmouth Nebraska.&#xA;&#xA;Rivers nearby: Platte joins Missouri River">
      <style basestylename="Scene Heading"/>
      <text>EXT. Mast farm - nIGHT</text>
    </para>
    <para>
      <style basestylename="Action"/>
      <text>Wheat fields. Etched in darkness and moonlight. For the moment it’s calm.</text>
    </para>
    <para>
      <style basestylename="Action"/>
      <text size="12" bold="1" color="#000000" bgcolor="#FFFFFF">TITLE: Nebraska, 1875.</text>
    </para>
    <para>
      <style basestylename="Action"/>
      <text>A WOMAN CRIES OUT, drawing our attention to a scant farm house in the distance.</text>
    </para>
```


More:
* http://www.kenttessman.com/2012/02/open-screenplay-format/
* https://sourceforge.net/projects/openscrfmt/

##### Fountain (Markdown)

Fountain is what happens when compute nerds write films. And it's becoming the fastest-moving format out there. Fountain is simply **markdown**, as used in Git repos. It's well documented, fast, and easily integrated into different software.

Fountain has been ported into PHP by Alex King (Fountain-PHP):
* https://github.com/alexking/Fountain-PHP

Parsing markdown is a standard operation used all over the place. For example: http://parsedown.org/


```markdown
INT. SMALL TOWN BANK - DAY

= Walter is denied an extension on his farm's loan

Walter sits across the desk from a BANK OFFICIAL who can’t stop wringing his hands.

BANK OFFICIAL
I’d really like to help. I really would...

WALTER
I’m not asking for a hand-out, George. It’s been a tough season. I just need more time.

BANK OFFICIAL
You’ve already exceeded the grace period. Twice.
```

More:
* https://fountain.io/faq

##### RTF

Rich Text is a very old standard that has been around for years. Very simple to parse using RTF reader libraries available on Packagist, e.g. https://github.com/henck/rtf-html-php.


```rtf
{\pard\plain \ql \caps\sb480\f66\fs24\sl180\s2\fi0\ri1440\li2160 EXT. Mast farm - nIGHT\par }
{\pard\plain \ql \sb240\f66\fs24\sl180\s3\fi0\ri1440\li2160 Wheat fields. Etched in darkness and moonlight. For the moment it's calm.\par }
{\pard\plain \ql \b\sb240\f66\fs24\sl180\s3\fi0\ri1440\li2160 TITLE: Nebraska, 1875.}
{\pard\plain \ql \sb240\f66\fs24\sl180\s3\fi0\ri1440\li2160 \par A WOMAN CRIES OUT, drawing our attention to a scant farm house in the distance.\par }
{\pard\plain \ql \caps\sb480\f66\fs24\sl180\s2\fi0\ri1440\li2160 EXT. Mast house - cONTINUOUS\par }
{\pard\plain \ql \sb240\f66\fs24\sl180\s3\fi0\ri1440\li2160 Perched on the house's front stair is HENRY MAST. He's 11 years old and anxious as anything. \par His mother's intermittent screams emanate from the house.\par Nearby his father WALTER MAST, 40's, smokes a pipe. Keeps his back to his son. Trying to hide the worry in his weathered face.\par More screams from inside.\par }
{\pard\plain \ql \caps\sb240\f66\fs24\sl180\s4\fi0\ri1800\li5040 Henry\par }
{\pard\plain \ql \sb0\f66\fs24\sl180\s6\fi0\ri3600\li3600 Was it like this with me?\par }
{\pard\plain \ql \caps\sb240\f66\fs24\sl180\s4\fi0\ri1800\li5040 Doctor\par }
{\pard\plain \ql \sb0\f66\fs24\sl180\s6\fi0\ri3600\li3600 I'm sorry, Walter.\par }
```

More:
* https://en.wikipedia.org/wiki/Rich_Text_Format


## Extracting text-based PDF screenplays

Occasionally you'll get annoying PDFs that you can't automatically parse with a language like C, PHP, or Java. In this case, if it's a text-based export from something like Final Draft, you can use the free and open source XPDF-based library **pdftotext**.

##### 1. Install pdftotext

On OS X, it is part of the **poppler** package:

```
brew install poppler-utils
```

On Linux, it's the same:

```bash
yum install poppler-utils # CentOS
apt-get install poppler-utils # Debian
```

##### 2. Convert from the command line (+/- password)

Usage from the command line using a file without password protection:

```bash
pdftotext script.pdf script.txt
```

And WITH password encryption:
```
pdftotext -upw 'password' script.pdf script.txt
```

##### Web Back-Ends

And called from a back-end web server process, e.g. in Laravel:

```php
$cmd = 'pdftotext -layout -upw '.$password_text.' '.$pdf_file_path.' '.$output_txt_path;
exec($cmd, $pdftotext_output, $exit_code);

$contents = file_get_contents($output_txt_path);
```

There are plenty of packages available for NPM:
* https://www.npmjs.com/browse/keyword/pdftotext



## Extracting image-based PDF screenplays

More often, - especially with older scripts - you'll have a PDF that contains image *scans* of each page. Script vendors often do this along with disabling printing etc, thinking it's a form of "copy protection".

For this type of file, you're going to need Optical Character Recognition (**OCR**).

First-off, OCR only works effectively with high-resolution image files, so you need to convert the PDF to **TIFF** format.

Note: there are plenty of packages available for NPM:
* https://www.npmjs.com/browse/keyword/tesseract

##### 1. Export to TIFF

Open the PDF in **Preview**, and use **File > Export** to save as a **TIFF** file. This can take a long time and produce a file that is dozens of GBs in size.

You can also do this programmatically with **ImageMagick**, obviously.

```bash
convert -density 300 /path/to/script.pdf -depth 8 -strip -background white -alpha off script.tiff
```

##### 2. Install Tesseract

**Tesseract** (https://github.com/tesseract-ocr/tesseract) is an open-source OCR engine that can be installed on OS X and/or Linux.

On OS X:

```bash
brew install tesseract
```

On Linux:

```bash
apt-get install tesseract-ocr
```

##### 3. Perform OCR on your TIFF file

OCR isn't perfect, so the file will need manual correction. But it's better than typing the thing out by hand, manually.

```bash
tesseract script.tiff script.txt
```
