---
layout: page
title: Simple Fields – better custom fields for WordPress
tagline: test
---

Simple Fields is a nice plugin indeed.


{% for post in site.posts %}
    <li>aaa<span>{{ post.date | date_to_string }}</span> &raquo; <a href="{{ BASE_PATH }}{{ post.url }}">{{ post.title }}</a></li>
  {% endfor %}
</

- list
- items

# headline

*bold*'
_and things_


<!--
{% gist parkr/931c1c8d465a04042403 jekyll-private-gist.markdown %}
-->
