<h1>ioSyncContentPlugin</h1>

<p>This plug in will allow you to sync content and databases from one server to another.
Many projects we work on include at least a staging server as well as a production
server. In many cases we need to update our local boxes with data that is either
on production or staging servers.</p>

<h1>Requirements</h1>

<ul>
  <li>Doctrine</li>
</ul>

<h1>Syncing Content</h1>

<p>To sync content from one server to another, type:<p>

<pre>
<code>
    php symfony io:sync-content SOURCE DESTINATION
</code>
</pre>

<p><b>NOTE</b>: The task will do nothing unless you pass what content you want synced. You
can sync the database, files and folders, or both by passing --include-database
OR --include-content. You can always pass both options if you want to sync
both.</p>

<p>The source and destination are references to the servers that are in your properties.ini
file (config/properties.ini). For example, if you had an entry labeled production:</p>

<pre>
<code>
    [production]
      user=username
      host=example.com
      dir=/var/www/project
      port=22
</code>
</pre>

<p>Then you can import content from it by typing:</p>

<pre>
<code>
    php symfony io:studio production localhost
</code>
</pre>

<p><b>NOTE</b>: localhost refers to your machine. It does not need to be defined in your
properties.ini file.</p>

<h1>Syncing Databases</h1>

<p>To sync a database, type:</p>

<pre>
<code>
    php symfony io:sync-content SOURCE DESTINATION --include-database
</code>
</pre>

<p>This will take a dump of the current database on SOURCE server and import it into
the DESTINATION. For example, if you wanted to import the database on your production
server to your computer:</p>

<pre>
</code>
    php symfony io:sync-content production localhost --include-database
</code>
</pre>

<p>This will take a dump of the database used on your production server and import
it into your localhost.</p>

<p>When the ioSyncContentPlugin dumps your database, you can tell it which tables
you don't want to dump. For example, if you don’t want to dump the blog_comments
table, you can do that. In the app.yml file put the models you would like to ignore.</p>

<pre>
<code>
    # /config/app.yml
    all:
      ioSyncContent:
        database_ignore:
          - BlogComments
</code>
</pre>

<h1>Syncing Files and Folders</h1>

<p>To sync files and folders, type:</p>

<pre>
<code>
  php symfony io:sync-content SOURCE DESTINATION --include-content
</code>
</pre>

<p>This will look in your app.yml file to see what you want to sync. An example of
the app.yml will look similar to this:</p>

<pre>
<code>
    all:
      ioSyncContent:
        content:
          - 'web/uploads'
</code>
</pre>

<p>So if you want to sync content from the production server to you local box, you
would type:</p>

<pre>
<code>
    php symfony io:sync-content production localhost --include-content
</code>
</pre>

<h1>Advanced Usage</h1>

<p>The io:sync-content task can take a number of different options. That can affect
the way it works.</p>

<p><b>--application=frontend</b><br/>
If you want to use something other than the frontend app, change it here. This
is used when syncing the database from one server to another.</p>

<p><b>--env=dev</b><br/>
If you have multiple environments set up, then you can change that by passing
this option.</p>

<p><b>--connection=doctrine</b><br/>
This refers to the connection name that is located in your databases.yml
</p>

<p><b>--rsync-options="-avz"</b><br/>
When you sync files and folders, the task will use rsync. Sometimes you will
want to change some of the options that rsync uses.</p>

<p><b>--dry-run</b><br/>
If you pass this option, only the output will be displayed and nothing will sync.</p>

<p><b>--mysqldump-options="--skip-opt --add-drop-table --create-options --disable-keys --extended-insert --set-charset"</b><br/>
If you are syncing databases, you can pass this option and change the way the
database is synced.</p>