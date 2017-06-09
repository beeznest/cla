
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

                {% include template ~ "/layout/login_form.tpl" %}
                {% include template ~ "/layout/register_form.tpl" %}
                <script>
                    $(document).on('ready', function () {
                        $("#notifications").load("{{ _p.web_main }}inc/ajax/online.ajax.php?a=get_users_online");
                    });
                </script>
                <div class="section-notifications">
                    <ul id="notifications">
                    </ul>
                    <ul class="option-session">
                        {% if _u.logged  == 0 %}
                            <li>
                                <a href="#" data-toggle="modal" data-target="#login_modal" aria-haspopup="true"
                                   aria-expanded="false">
                                    <i class="fa fa-sign-in fa-lg"></i> {{ "SignIn"|get_lang }}
                                </a>
                            </li>
                            <li>
                                <a href="#" data-toggle="modal" data-target="#register_modal" aria-haspopup="true"
                                   aria-expanded="false">
                                    <i class="fa fa-pencil-square-o fa-lg"></i> {{ 'SignUp'|get_lang }}
                                </a>
                            </li>
                            </li>
                        {% endif %}
                    </ul>
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
