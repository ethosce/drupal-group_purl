This module provides Purl plugins to support Group module.

With this module, it is easy to make a Group-based site that keeps the group context for views, adding content, and browsing through a group.

It provides plugins for:

 - Group Provider - Add the Group entity type to the Purl system
 - Group Prefix Method - Apply the group context to any path that begins with the matched Group prefix, but is not exactly the same -- this provides automatic context settings to nodes/content accessed via the group path, as well as rewriting all URLs on the page to prepend the group path.
 - Group Context - the main context provider that gets set when there's a match in Purl
 - Views default_argument plugin - automatically filter a view when the group context is active.
