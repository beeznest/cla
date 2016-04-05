{%
    extends hide_header == true
    ? template ~ "/layout/blank.tpl"
    : template ~ "/layout/layout_1_col.tpl"
%}

{% block content %}

{% if wizard %}
    <div class="page-header">
        <div class="wizard text-center">
            <a class="current"><span class="badge">1</span> {{ "Register" | get_plugin_lang('BuyCoursesPlugin') }}</a>
            <a><span class="badge">2</span> {{ "Payment" | get_plugin_lang('BuyCoursesPlugin') }}</a>
            <a><span class="badge badge-inverse">3</span> {{ "RegisterSubscriptors" | get_plugin_lang('BuyCoursesPlugin') }}</a>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $("#breadcrumb-bar").remove();
        });
    </script>
{% else %}
    {{ inscription_header }}
{% endif %}
{{ inscription_content }}
{{ form }}
{{ text_after_registration }}

{% endblock %}
