using 'main.bicep'

param adminIpAddress = '194.54.144.75/32'
param vmAdminUsername = 'azureadmin'
param vmAdminPublicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAILAhcY/cKe3kILtC3+VS0IC8bzEqxIuP03xZgPU258DX nevaquit@gmail.com'
param environment = 'prod'
param location = 'eastus2'
param vmSize = 'Standard_D2s_v3'
param vmZone = ''
