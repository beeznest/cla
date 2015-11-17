{% include template ~ "/layout/main_header.tpl" %}
{#
    show_header and show_footer templates are only called when using the
    Display::display_header and Display::display_footer
    for backward compatibility we suppose that the default layout is one column
    which means using a div with class span12
#}
{% if show_header == true %}
    <div class="container">
        {% include template ~ "/layout/page_body.tpl" %}
        <section id="main_content">
{% endif %}
