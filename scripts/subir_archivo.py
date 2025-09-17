import argparse
import os
from azure.storage.blob import BlobServiceClient
from azure.core.exceptions import AzureError

def upload_file_to_azure_blob(file_path: str, connection_string: str, container_name: str, blob_name: str) -> None:
    """
    Sube un archivo a Azure Blob Storage.
    """
    try:
        # Crea una instancia del cliente de servicio de blob usando la cadena de conexión.
        blob_service_client = BlobServiceClient.from_connection_string(connection_string)

        # Obtiene una referencia al cliente del contenedor.
        container_client = blob_service_client.get_container_client(container_name)

        # Crea el contenedor si no existe.
        if not container_client.exists():
            print(f"Creando contenedor '{container_name}'...")
            container_client.create_container()

        # Obtiene una referencia al blob.
        blob_client = container_client.get_blob_client(blob_name)

        print(f"Subiendo el archivo '{file_path}' a Azure Blob Storage como '{blob_name}'...")
        
        # Abre el archivo local en modo binario y lo sube.
        with open(file_path, "rb") as data:
            blob_client.upload_blob(data, overwrite=True)

        print(f"Archivo subido exitosamente a Azure Blob Storage.")

    except AzureError as ex:
        print('Fallo en la subida a Azure Blob Storage.')
        print('Detalles del error:', ex)
    except Exception as ex:
        print('Ocurrió una excepción inesperada.')
        print('Detalles del error:', ex)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Sube un archivo a Azure Blob Storage.')
    
    # Define los argumentos que el script necesita.
    parser.add_argument('local_file_path', type=str, help='La ruta del archivo local a subir.')
    parser.add_argument('container_name', type=str, help='El nombre del contenedor de destino en Azure.')
    parser.add_argument('connection_string', type=str, help='La cadena de conexión de Azure Blob Storage.')
    parser.add_argument('blob_destination_name', type=str, help='El nombre que tendrá el archivo en el blob, incluyendo la subcarpeta virtual si aplica (ej. "ventas/datos.csv").')
    
    # Analiza los argumentos de la línea de comandos.
    args = parser.parse_args()
    
    # Llama a la función de subida con los argumentos recibidos.
    upload_file_to_azure_blob(
        file_path=args.local_file_path,
        connection_string=args.connection_string,
        container_name=args.container_name,
        blob_name=args.blob_destination_name
    )