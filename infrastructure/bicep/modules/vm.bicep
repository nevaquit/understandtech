param location string
param environment string
param tags object
param vmSubnetId string
param nsgId string
param adminUsername string
param adminPublicKey string
param cloudInitContent string
param keyVaultName string

@description('VM size — must have capacity in the target region/zone.')
param vmSize string = 'Standard_D2s_v3'

@description('Availability zone for the web VM (empty string = regional).')
param vmZone string = ''

@description('OS disk size in GB.')
param osDiskSizeGB int = 64

var vmName = 'understandtech-web-${environment}'
var effectiveOsDiskSizeGB = environment == 'staging' ? min(osDiskSizeGB, 32) : osDiskSizeGB
var vmZones array = empty(vmZone) ? [] : [ vmZone ]

resource publicIp 'Microsoft.Network/publicIPAddresses@2024-01-01' = {
  name: '${vmName}-pip'
  location: location
  zones: vmZones
  tags: tags
  sku: {
    name: 'Standard'
  }
  properties: {
    publicIPAllocationMethod: 'Static'
  }
}

resource nic 'Microsoft.Network/networkInterfaces@2024-01-01' = {
  name: '${vmName}-nic'
  location: location
  zones: vmZones
  tags: tags
  properties: {
    ipConfigurations: [
      {
        name: 'ipconfig1'
        properties: {
          subnet: {
            id: vmSubnetId
          }
          privateIPAllocationMethod: 'Dynamic'
          publicIPAddress: {
            id: publicIp.id
          }
        }
      }
    ]
    networkSecurityGroup: {
      id: nsgId
    }
  }
}

resource vm 'Microsoft.Compute/virtualMachines@2024-03-01' = {
  name: vmName
  location: location
  tags: tags
  zones: vmZones
  identity: {
    type: 'SystemAssigned'
  }
  properties: {
    hardwareProfile: {
      vmSize: vmSize
    }
    osProfile: {
      computerName: vmName
      adminUsername: adminUsername
      linuxConfiguration: {
        disablePasswordAuthentication: true
        ssh: {
          publicKeys: [
            {
              path: '/home/${adminUsername}/.ssh/authorized_keys'
              keyData: adminPublicKey
            }
          ]
        }
      }
      customData: base64(cloudInitContent)
    }
    storageProfile: {
      imageReference: {
        publisher: 'Canonical'
        offer: '0001-com-ubuntu-server-jammy'
        sku: '22_04-lts-gen2'
        version: '22.04.202605030'
      }
      osDisk: {
        createOption: 'FromImage'
        diskSizeGB: effectiveOsDiskSizeGB
        managedDisk: {
          storageAccountType: 'Premium_LRS'
        }
      }
    }
    networkProfile: {
      networkInterfaces: [
        {
          id: nic.id
        }
      ]
    }
  }
}

resource keyVault 'Microsoft.KeyVault/vaults@2023-07-01' existing = {
  name: keyVaultName
}

resource kvSecretsUser 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(keyVault.id, vm.id, '4633458b-17de-408a-b874-0445c86b69e6')
  scope: keyVault
  properties: {
    roleDefinitionId: subscriptionResourceId('Microsoft.Authorization/roleDefinitions', '4633458b-17de-408a-b874-0445c86b69e6')
    principalId: vm.identity.principalId
    principalType: 'ServicePrincipal'
  }
}

output publicIpAddress string = publicIp.properties.ipAddress
output vmPrincipalId string = vm.identity.principalId
