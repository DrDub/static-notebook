# PHP Static Notebooks

This is experimental software providing a tiny bit of the functionality present in Jupyter notebooks in a very simple PHP script (`static-notebook.php`, about 100 loc).

The idea behind these static notebook is self-modifying code: the new cells are appended at the end of the script and re-executed every time (although a cache using [shmop](https://www.php.net/manual/en/book.shmop.php) is planned).

To use it, just copy static-notebook.php to the notebook you want to start anew, then launch the php integrated web server:

```bash
cp static-notebook.php my-notebook.php
# composer require ...
php -S 0.0.0.0:8085
```

Then open http://localhost:8085/my-notebook.php .

It currently supports two types of cells, PHP and HTML. It also has the functionality of copying an early cell to the new cell, for editing and submitting a modification of the code. It is also possible to download the php code of all the cells (it gives it a txt extension to simplify download).

The script will valiantly refuse to run in a webserver different from the php development server.

## Limitations

Too many to list, but here are the most salient ones:

* No support for "use" statements, all the classes has to be fully qualified (or you can edit the script to include use at the top as needed). This will be addressed by handling the use statements separately.
* No error handling. If you have errors, you'll need to edit the php file by hand. This will be addressed with proper error capture and rejecting the new cell until it doesn't throw errors.
* No caching, all the cells are executed, including any of their side effects. This will be addressed using shmop as mentioned above.
* Many others, feel free to document it in the issue tracker.

Minor:

* No CSS
* No syntax highlighting
* No JS (beyond a single scrollIntoView)
* No ability to install packages from the page (need to be installed command-line).

## What to use instead

Self-modifying code is a bad idea. Self-modifying code in a web server is a fireable offense. Instead, use:

* https://github.com/Litipk/Jupyter-PHP
* https://psysh.org/


