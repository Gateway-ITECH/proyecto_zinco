import argparse
from azure.storage.blob import BlobServiceClient
from azure.core.exceptions import AzureError

def test_azure_blob_connection(connection_string: str, container_name: str) -> bool:
    """
    Prueba la conexión a un contenedor específico en Azure Blob Storage.
    """
    try:
        blob_service_client = BlobServiceClient.from_connection_string(connection_string)
        container_client = blob_service_client.get_container_client(container_name)
        
        print(f"Probando conexión al contenedor '{container_name}'...")
        container_client.get_container_properties()

        print("¡Conexión exitosa a Azure Blob Storage!")
        return True

    except AzureError as ex:
        print("Fallo en la conexión a Azure Blob Storage.")
        print("Detalles del error:", ex)
        return False
    except Exception as ex:
        print("Ocurrió una excepción inesperada.")
        print("Detalles del error:", ex)
        return False

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Prueba la conexión a un contenedor de Azure Blob Storage.')
    
    # Agrega los argumentos posicionales.
    parser.add_argument('container_name', type=str, help='El nombre del contenedor a probar.')
    parser.add_argument('connection_string', type=str, help='La cadena de conexión de Azure Blob Storage.')
    
    # Analiza los argumentos de la línea de comandos.
    args = parser.parse_args()
    
    # Llama a la función de prueba con los argumentos recibidos.
    is_connected = test_azure_blob_connection(args.connection_string, args.container_name)
    
    if is_connected:
        print("La aplicación puede comunicarse con el contenedor especificado.")
    else:
        print("La aplicación no pudo comunicarse con el contenedor. Revisa los argumentos proporcionados.")