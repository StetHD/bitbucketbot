# Bitbucket Bot

The Bitbucket bot is a helpful little robot that does things when you push changes to Bitbucket.

At the moment it receives the Bitbucket post-hook payload, does an API-check for changes then looks for commands to update Asana.

What are commands you ask?

## Commands

Commands are inspired by [Donedone's VCS integration](http://www.getdonedone.com/api/gitsvnintegration/):

For the Bitbucket Bot they look like:

```
[#123 Message tags:tag1,tag2 reassign:somechap]
```

The command is broken down into:

1. Asana task ID - the task we're modifying with this command
2. Message - a simple text message (more on this below)
3. Tags - add some tags to the task
4. Reassign - re-assign the Asana task to someone

The Asana task ID is fairly straightforward and can be retrieved by selected a task and then copying the last number from the URL.

The message is a plain text message that will be posted as a comment to the Asana task. By default the message is:

```
This task was referenced by commit https://bitbucket.org/<workspace>/<repo>/commits/<hash> with the message: <message>
```

If you omit a message it will use the full commit message (minus any commands). In reality the message is just whatever's left over when parsing the command so if you malform a command, it will show up in the message.

The tags are straight forward; if a tag doesn't exist it will be created before being added. At the moment there is no way of adding tags with spaces in them, this is on the todo list.

The reassignment tries to be fairly smart. In a perfect world you'd just put in the e-mail address of people on Asana and it will work as you expect. As those aren't always available or desirable to be in VCS commits, you can use a prefix and if it matches the prefix of an Asana user's name or e-mail address, it'll use that (first one it finds).

For instance, you put in:

```
[#123 Message tags:tag1 reassign:john.noel]
```

If my Asana e-mail address was john.noel+asana@gmail.com then the above would match. Likewise if you used:

```
[#123 Message tags:tag1 reassign:john]
```

It would match if I was the only John in the Asana workspace users. All user searches are local to your workspace.

## Installing

Clone the repo, grab [Composer](https://getcomposer.org/) and do:

```
composer install
```

Once that's done it's magic, copy config/config.php.dist to config/config.php and fill in the blanks. While there are details for Asana OAuth2 credentials in there, the Asana integration at the moment only uses API keys. We (Rckt) have set up a specific Asana user to do all the commenting.

You will need OAuth Bitbucket details, there is a a very basic script in web/bitbucket-oauth.php to help you along with this.

Create db/ and logs/ directories and make them world writable (or at least writable by the webserver).

Expose the web/ directory to the world via a webserver.

## Using

In the Bitbucket repo, update the settings and add a POST hook to the web/connector.php URL.

All requests to the connector are logged in logs/request.log and application details are logged in logs/app.log so you can debug from there.


## Security

Even though the Bitbucket POST hook sends changeset details in the payload, the Bitbucket bot does not trust these and does an authorised API call to get the latest details.

It does however grab the repository from the payload to prevent mapping/setup issues. If you're getting junk requests, try firewalling the Bitbucket IPs.
