param location string
param environment string
param tags object
param adminIpAddress string
param cloudflareIpv4Prefixes array

var vnetName = 'understandtech-vnet-${environment}'
var nsgName = 'understandtech-nsg-${environment}'
var postgresDnsZoneName = 'privatelink.postgres.database.azure.com'

var cloudflareHttpsRules = [for (prefix, i) in cloudflareIpv4Prefixes: {
  name: 'Allow-Cloudflare-HTTPS-${i}'
  properties: {
    priority: 200 + i
    direction: 'Inbound'
    access: 'Allow'
    protocol: 'Tcp'
    sourceAddressPrefix: prefix
    sourcePortRange: '*'
    destinationAddressPrefix: '*'
    destinationPortRange: '443'
  }
}]

var nsgSecurityRules = concat(
  [
    {
      name: 'Allow-SSH-Admin'
      properties: {
        priority: 100
        direction: 'Inbound'
        access: 'Allow'
        protocol: 'Tcp'
        sourceAddressPrefix: adminIpAddress
        sourcePortRange: '*'
        destinationAddressPrefix: '*'
        destinationPortRange: '22'
      }
    }
  ],
  cloudflareHttpsRules,
  [
    {
      name: 'Deny-All-Inbound'
      properties: {
        priority: 4096
        direction: 'Inbound'
        access: 'Deny'
        protocol: '*'
        sourceAddressPrefix: '*'
        sourcePortRange: '*'
        destinationAddressPrefix: '*'
        destinationPortRange: '*'
      }
    }
  ]
)

resource vnet 'Microsoft.Network/virtualNetworks@2024-01-01' = {
  name: vnetName
  location: location
  tags: tags
  properties: {
    addressSpace: {
      addressPrefixes: ['10.10.0.0/16']
    }
    subnets: [
      {
        name: 'snet-web'
        properties: {
          addressPrefix: '10.10.1.0/24'
          networkSecurityGroup: {
            id: nsg.id
          }
        }
      }
      {
        name: 'snet-postgres'
        properties: {
          addressPrefix: '10.10.2.0/24'
          delegations: [
            {
              name: 'postgres-delegation'
              properties: {
                serviceName: 'Microsoft.DBforPostgreSQL/flexibleServers'
              }
            }
          ]
        }
      }
    ]
  }
}

resource nsg 'Microsoft.Network/networkSecurityGroups@2024-01-01' = {
  name: nsgName
  location: location
  tags: tags
  properties: {
    securityRules: nsgSecurityRules
  }
}

resource postgresPrivateDnsZone 'Microsoft.Network/privateDnsZones@2020-06-01' = {
  name: postgresDnsZoneName
  location: 'global'
  tags: tags
}

resource postgresPrivateDnsLink 'Microsoft.Network/privateDnsZones/virtualNetworkLinks@2020-06-01' = {
  parent: postgresPrivateDnsZone
  name: 'understandtech-postgres-link-${environment}'
  location: 'global'
  properties: {
    registrationEnabled: false
    virtualNetwork: {
      id: vnet.id
    }
  }
}

output vnetId string = vnet.id
output vmSubnetId string = vnet.properties.subnets[0].id
output postgresSubnetId string = vnet.properties.subnets[1].id
output nsgId string = nsg.id
output postgresPrivateDnsZoneName string = postgresDnsZoneName
