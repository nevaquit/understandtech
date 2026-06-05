targetScope = 'subscription'

@description('Office or admin IP for SSH access (CIDR, e.g. 203.0.113.10/32).')
param adminIpAddress string

@description('Linux admin username for the web VM.')
param vmAdminUsername string = 'azureadmin'

@description('SSH public key for VM admin access.')
param vmAdminPublicKey string

@allowed(['prod', 'staging'])
param environment string = 'prod'

param location string = 'eastus2'

@description('Cloudflare IPv4 CIDR ranges allowed to reach origin HTTPS.')
param cloudflareIpv4Prefixes array = [
  '173.245.48.0/20'
  '103.21.244.0/22'
  '103.22.200.0/22'
  '103.31.4.0/22'
  '141.101.64.0/18'
  '108.162.192.0/18'
  '190.93.240.0/20'
  '188.114.96.0/20'
  '197.234.240.0/22'
  '198.41.128.0/17'
  '162.158.0.0/15'
  '104.16.0.0/13'
  '104.24.0.0/14'
  '172.64.0.0/13'
  '131.0.72.0/22'
]

var tags = {
  environment: environment
  product: 'understandtech.app'
  costCenter: 'platform'
}

var resourceGroupName = 'understandtech-${environment}-rg'

resource rg 'Microsoft.Resources/resourceGroups@2024-03-01' = {
  name: resourceGroupName
  location: location
  tags: tags
}

module network 'modules/network.bicep' = {
  name: 'network-${environment}'
  scope: rg
  params: {
    location: location
    environment: environment
    tags: tags
    adminIpAddress: adminIpAddress
    cloudflareIpv4Prefixes: cloudflareIpv4Prefixes
  }
}

module data 'modules/data.bicep' = {
  name: 'data-${environment}'
  scope: rg
  params: {
    location: location
    environment: environment
    tags: tags
    postgresSubnetId: network.outputs.postgresSubnetId
    privateDnsZoneName: network.outputs.postgresPrivateDnsZoneName
  }
}

module monitoring 'modules/monitoring.bicep' = {
  name: 'monitoring-${environment}'
  scope: rg
  params: {
    location: location
    environment: environment
    tags: tags
  }
}

module vm 'modules/vm.bicep' = {
  name: 'vm-${environment}'
  scope: rg
  params: {
    location: location
    environment: environment
    tags: tags
    vmSubnetId: network.outputs.vmSubnetId
    nsgId: network.outputs.nsgId
    adminUsername: vmAdminUsername
    adminPublicKey: vmAdminPublicKey
    cloudInitContent: loadTextContent('../runner/cloud-init.yaml')
    keyVaultId: data.outputs.keyVaultId
    keyVaultName: data.outputs.keyVaultName
  }
}

output vmPublicIp string = vm.outputs.publicIpAddress
output postgresFqdn string = data.outputs.postgresPrivateFqdn
output redisHostName string = data.outputs.redisHostName
output keyVaultUri string = data.outputs.keyVaultUri
output storageAccountName string = data.outputs.storageAccountName
output resourceGroupName string = rg.name
