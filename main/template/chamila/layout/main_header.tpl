<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{{ document_language }}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--><html lang="{{ document_language }}" class="no-js"> <!--<![endif]-->
<head>
{% block head %}
{% include template ~ "/layout/head.tpl" %}
{% endblock %}
</head>
<body dir="{{ text_direction }}" class="{{ section_name }} {{ login_class }}">
    
<script>
$(document).ready(function() {
    
    $("[name\=submitAuth\]").on("click", function() {
        var login = $("#login").val();
        var password = $("#password").val();
        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            type: "POST",
            url: "{{ ajax_path }}" + "?a=signIn",
            data: 'login=' + login + '&password=' + password,
            beforeSend : function() {
                $("#returnMessage").html('<div class="three-quarters-loader"></div>');
            },
            success: function(response) {
                try {
                    response = JSON.parse(response);
                    $("#returnMessage").html(response['message']);
                    $(location).attr('href',response['url']);
                } catch (e) {
                    $("#returnMessage").html(response);
                }
            }
        });
        return false;
    });
    
    $("[name\=submitReg\]").on("click", function() {
        var firstname = $("#firstname").val();
        var lastname = $("#lastname").val();
        var username = $("#username").val();
        var pass1 = $("#pass1").val();
        var pass2 = $("#pass2").val();
        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            type: "POST",
            url: "{{ ajax_path }}" + "?a=signUp",
            data: 'firstname=' + firstname + '&lastname=' + lastname + '&username=' + username + '&pass1=' + pass1 + '&pass2=' + pass2,
            beforeSend : function() {
                $("#returnMessage2").html('<div class="three-quarters-loader"></div>');
            },
            success: function(response) {
                try {
                    response = JSON.parse(response);
                    $("#returnMessage2").html(response['message']);
                    $.ajax({
                        contentType: "application/x-www-form-urlencoded",
                        type: "POST",
                        url: "{{ ajax_path }}" + "?a=signIn",
                        data: 'login=' + username + '&password=' + pass1,
                        success: function(authLogIn) {
                            if (authLogIn) {
                                $(location).attr('href',response['url']);
                            }
                        }
                    });
                } catch (e) {
                    $("#returnMessage2").html(response);
                }
            }
        });
        return false;
    });
});
</script>

<noscript>{{ "NoJavascript"|get_lang }}</noscript>

<!-- Display the Chamilo Uses Cookies Warning Validation if needed -->
{% if displayCookieUsageWarning == true %}
    <!-- If toolbar is displayed, we have to display this block bellow it -->
    {% if toolBarDisplayed == true %}
        <div class="displayUnderToolbar" >&nbsp;</div>
    {% endif %}
    <form onSubmit="$(this).toggle('slow')" action="" method="post">
        <input value=1 type="hidden" name="acceptCookies"/>
        <div class="cookieUsageValidation">
            {{ "YouAcceptCookies" | get_lang }}
            <span style="margin-left:20px;" onclick="$(this).next().toggle('slow'); $(this).toggle('slow')">
                ({{"More" | get_lang }})
            </span>
            <div style="display:none; margin:20px 0;">
                {{ "HelpCookieUsageValidation" | get_lang}}
            </div>
            <span style="margin-left:20px;" onclick="$(this).parent().parent().submit()">
                ({{"Accept" | get_lang }})
            </span>
        </div>
    </form>
{% endif %}

{% if show_header == true %}

<div id="page-wrap"><!-- page section -->

    {% block help_notifications %}
    <ul id="navigation" class="notification-panel">
        {{ help_content }}
        {{ bug_notification_link }}
    </ul>
    {% endblock %}

    {% block topbar %}
        {% include template ~ "/layout/topbar.tpl" %}
        {% if show_toolbar == 1 %}
            <div class="clear-header"></div>
        {% endif %}
    {% endblock %}
        <header>
            <div class="extra-header">{{ header_extra_content }}</div>
            <section id="main" class="container">
                {% if plugin_header_main %}
                <div class="row">
                    <div class="col-lg-12">
                        {{ plugin_header_main }}
                    </div>
                </div>
                {% endif %}
                <div class="row">
                    <div class="col-lg-3">
                        <div class="logo">
                            {{ logo }}
                        </div>
                    </div>
                    <div class="col-lg-9">
                    {% if _u.logged == 0 %}
                        <div class="login pull-right">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="modal" data-target="#login_modal" aria-haspopup="true" aria-expanded="false">
                                  <i class="fa fa-sign-in fa-lg"></i> Iniciar Sesión <span class="caret"></span>
                                </button>
                                {% include template ~ "/layout/login_form.tpl" %}
                            </div>
                            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="modal" data-target="#register_modal" aria-haspopup="true" aria-expanded="false">
                                  <i class="fa fa-pencil-square-o fa-lg"></i> Registrate <span class="caret"></span>
                            </button>
                            {% include template ~ "/layout/register_form.tpl" %}
                        </div>
                    {% endif %}
                    </div>
                </div>
            </section>
            <section id="menu-bar">
                {# menu #}
                {% block menu %}
                {% include template ~ "/layout/menu.tpl" %}
                {% endblock %}
            </section>
            
        </header>
    
{% endif %}
