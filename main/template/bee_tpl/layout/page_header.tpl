<div id="navigation" class="notification-panel">
    {{ help_content }}
    {{ bug_notification }}
</div>
{% block topbar %}
    {% include template ~ "/layout/topbar.tpl" %}
{% endblock %}
<div class="extra-header">{{ header_extra_content }}</div>
<div class="top-header">
    <div class="container">
    <div class="row">
        <div class="col-md-6"></div>
        <div class="col-md-6">
            <script>
               $(document).on('ready', function () {
                   $("#notifications").load("{{ _p.web_main }}inc/ajax/online.ajax.php?a=get_users_online");
               });
            </script>
            <div class="section-notifications">
                <ul id="notifications">
                </ul>
            </div>
            {{ accessibility }}
        </div>
    </div>
    </div>
</div>
<header id="header-section" class="header-movil">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="logo">
                    {{ logo }}
                </div>
            </div>
            <div class="col-md-9">
               
            </div>
        </div>
    </div>
</header>
{% block menu %}
    {% include template ~ "/layout/menu.tpl" %}
{% endblock %}
{% include template ~ "/layout/course_navigation.tpl" %}
