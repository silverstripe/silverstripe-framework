<?xml version="1.0"?>
<definitions xmlns="http://schemas.xmlsoap.org/wsdl/" 
xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:tns="{$ServiceURL}wsdl"
targetNamespace="{$ServiceURL}wsdl">
	<% control Methods %>
	<message name="{$Name}Request" targetNamespace="$CurrentPage.TargetNamespace">
		<% control Arguments %>
		<part name="$Name" type="$Type"/>
		<% end_control %>
	</message>
	<message name="{$Name}Response" targetNamespace="$CurrentPage.TargetNamespace">
		<part name="{$Name}Return" type="$ReturnType" />
	</message>
	<% end_control %>

	<portType name="SapphireSOAP_methodsPortType">
		<% control Methods %>
		<operation name="$Name">
			<input message="tns:{$Name}Request"/>
			<output message="tns:{$Name}Response"/>
		</operation>
		<% end_control %>
	</portType>
	<binding name="SapphireSOAP_methodsBinding" type="tns:SapphireSOAP_methodsPortType">
		<soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
		<% control Methods %>
		<operation name="$Name">
			<soap:operation soapAction="$CurrentPage.ServiceURL?method=$Name" style="rpc"/>
			<input>
				<soap:body use="encoded" namespace="$CurrentPage.TargetNamespace" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</input>
			<output>
				<soap:body use="encoded" namespace="$CurrentPage.TargetNamespace" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</output>
		</operation>
		<% end_control %>
	</binding>
	<service name="SapphireSOAP_methods">
		<port name="SapphireSOAP_methodsPort" binding="tns:SapphireSOAP_methodsBinding">
			<soap:address location="$CurrentPage.ServiceURL" />
		</port>
	</service>
</definitions>

