<script>
//plugins/generic/ompxml/templates/settingsForm.tpl
	$(function() {ldelim}
		// Attach the form handler.
		$('#ompxmlSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="ompxmlSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="ompxmlSettingsFormNotification"}

	<div id="description">{translate key="plugins.generic.ompxml.manager.settings.description"}</div>

	{fbvFormArea id="ompxmlSettingsFormArea"}
	




{fbvFormSection}
       {fbvElement type="text" id="email" value=$email label="Email" maxlength="100" size=$fbvStyles.size.MEDIUM}
       {fbvElement type="text" id="username" value=$username label="Nome de Usuário" maxlength="50" size=$fbvStyles.size.MEDIUM}
   {/fbvFormSection}
   {fbvFormButtons}

{/fbvFormArea}


	

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
