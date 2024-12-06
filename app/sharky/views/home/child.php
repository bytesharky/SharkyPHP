{% extends 'layout.html' %}

{% block title %}这里是child页 - 标题{% endblock %}

{% block content %}
    <p>这里是child页 - 内容</p>
{% endblock %}

{% include 'include.html' %}