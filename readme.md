# gihp -- Git in PHP

[![Build Status](https://travis-ci.org/vierbergenlars/gihp.png?branch=master)](https://travis-ci.org/vierbergenlars/gihp)

Gihp is a set of classes to work with git repositories in pure PHP.
Not a single line of shell code is executed.

**Recently a full-history rewrite was executed** https://gist.github.com/vierbergenlars/5191906

## Installation

Add `vierbergenlars/gihp` to your `composer.json` or run `composer.phar require vierbergenlars/gihp`

```json
{
    "require": {
        "vierbergenlars/gihp": "*"
    }
}
```

## [API documentation](http://vierbergenlars.github.com/gihp)

Only the API documentation for the latest version is available online.
Earlier versions are available in `gh-pages` branch.

## Basic usage

Opening a repository and getting basic information is a piece of cake.
```php
<?php

use gihp\Repository;
use gihp\IO\File;

$io = new File('.');
$repo = new Repository($io); // Load the repository that lives in the working directory

$branches = $repo->getBranches(); // Loads all branches in the repository

echo "Branches in this repo: \n";
foreach($branches as $name=>$branch) {
    // Branches have their name as key, and a gihp\Branch object as value.
    echo $name."\n";
}


$tags = $repo->getTags(); // Loads all tags in the repository
echo "Tags in this repo: \n";

foreach($tags as $name=>$tag) {
    echo $name."\n";
}
```

But what if you want to learn more about a tag?


```php
<?php

// ...

$tag = $repo->getTag('v0.1.0'); // Load the tag "v0.1.0", returns gihp\Tag

echo "About the 0.1.0 version: \n";

$tag->getName(); //returns "v0.1.0", obviously.

// Gihp automatically determines the type of the tag.
// When it is an annotated tag, functions are called on that annotated tag.
// When it's a normal tag, functions are called on the commit it refers to.

echo 'Message: '.$tag->getMessage()."\n"; // Gets the message associated with the tag.
echo 'Created by: '.$tag->getAuthor().' on '.$tag->getDate()."\n"; // Gets the person associated with the tag.
echo 'Commit '.$tag->getCommit()->getSHA1()."\n"; // But this will surely always fetch the commit object to act upon.
```

Doing stuff with branches also is perfectly fine


```php
<?php

// ...

$branch = $repo->getBranch('master'); // Load the master branch, returns gihp\Branch

echo "About the master branch: \n";

$branch->getName(); // "master"

echo 'Last commit by:'.$branch->getHeadCommit()->getAuthor()."\n";

$tree = $branch->getTree(); // A gihp\Tree

echo 'Contents of readme.md:'."\n".$tree->getFile('readme.md');
```
