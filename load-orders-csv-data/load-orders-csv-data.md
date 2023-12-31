# Load CSV data from OCI Object Store

## Introduction

To load data from Object Storage to HeatWave, you need to specify the location of the file or folder objects in your Object Storage.

1. Use [Resource Principal](https://docs.oracle.com/en-us/iaas/autonomous-database-serverless/doc/resource-principal-enable.html) - It is recommended that you use Resource Principal-based approach for access to data in Object Storage for more sensitive data as this approach is more secure.

2. Use [Pre-Authenticated Request URLs (PARs)](https://docs.oracle.com/en-us/iaas/Content/Object/Tasks/usingpreauthenticatedrequests.htm) - If you choose to use PARs, we recommend that you use read-only PARs with Lakehouse and specify short expiration dates for your PARs. The expiration dates should align with your loading schedule. 

Since we are using a sample data set, we will make use of PAR in this LiveLab. We already have several tables available in HeatWave that have been loaded from MySQL.

We will now load the DELIVERY_ORDERS table from the Object Store. This is a large table with 30 million rows and contains information about the delivery vendor for orders.

### Objectives

- Create PAR Link for the  "delivery_order" files
- Run Autoload to infer the schema and estimate capacity
- Load complete DELIVERY table from Object Store into MySQL HeatWave

### Prerequisites

- An Oracle Trial or Paid Cloud Account
- Some Experience with MySQL Shell
- Completed Lab 5

## Task 1: Create the PAR Link for the "delivery_order" files

1. Create a PAR URL for all of the **order folder** objects with a prefix

    - a. From your OCI console, navigate to your lakehouse-files bucket in OCI.
    - b. Select the folder —> order and click the three vertical dots.

        ![Select  folder](./images/storage-delivery-orders-folder.png "storage delivery order folder")

    - c. Click on ‘Create Pre-Authenticated Request’
    - d. Click to select the ‘Objects with prefix’ option under ‘PreAuthentcated Request Target’.
    - e. Leave the ‘Access Type’ option as-is: ‘Permit object reads on those with the specified prefix’.
    - g. Click to select the ‘Enable Object Listing’ checkbox.
    - h. Click the ‘Create Pre-Authenticated Request’ button.

       ![Create Folder PAR](./images/storage-delivery-orders-folder-page.png "storage delivery order folder page")

    - i. Click the ‘Copy’ icon to copy the PAR URL.
    - j. Save the generated PAR URL; you will need it later.
    - k. You can test the URL out by pasting it in your browser. It should return output like this:

        ![List folder file](./images/storage-delivery-orders-folder-list.png "storage delivery order folder list")

2. Save the generated PAR URL; you will need it in the next task

## Task 2: Connect to your MySQL HeatWave system using Cloud Shell

1. If not already connected with SSH, on Command Line, connect to the Compute instance using SSH ... be sure replace the  "private key file"  and the "new compute instance ip"

     ```bash
    <copy>ssh -i private_key_file opc@new_compute_instance_ip</copy>
     ```

2. If not already connected to MySQL then connect to MySQL using the MySQL Shell client tool with the following command:

    ```bash
    <copy>mysqlsh -uadmin -p -h 10.0.1... --sql </copy>
    ```

    ![MySQL Shell Connect](./images/mysql-shell-login.png " mysql shell login")

3. List schemas in your heatwave instance

    ```bash
        <copy>show databases;</copy>
    ```

    ![Databse Schemas](./images/list-schemas-after.png "list schemas after")

4. Change to the mysql\_customer\_orders database

    Enter the following command at the prompt

    ```bash
    <copy>USE mysql_customer_orders;</copy>
    ```

5. To see a list of the tables available in the mysql\_customer\_orders schema

    Enter the following command at the prompt

    ```bash
    <copy>show tables;</copy>
    ```

    You are now ready to use Autoload to load a table from the object store into MySQL HeatWave

## Task 3: Run Autoload to infer the schema and estimate capacity required for the DELIVERY table in the Object Store

1. The DELIVERY information for orders is contained in the delivery-orders-*1*.csv files in object store for which we have created a PAR URL in the earlier task. Enter the following commands one by one and hit Enter.

2. This sets the schema we will load table data into. Don’t worry if this schema has not been created. Autopilot will generate the commands for you to create this schema if it doesn’t exist.

    ```bash
    <copy>SET @db_list = '["mysql_customer_orders"]';</copy>
    ```

3. This sets the parameters for the table name we want to load data into and other information about the source file in the object store. Substitute the **(PAR URL)** below with the one you generated in the previous task:

    ```bash
    <copy>SET @dl_tables = '[{
    "db_name": "mysql_customer_orders",
    "tables": [{
        "table_name": "delivery_orders",
        "dialect": {
            "format": "csv",
            "field_delimiter": "\\t",
            "record_delimiter": "\\r\\n",
            "has_header": true,
            "is_strict_mode": false},
            "file": [{"par": "(PAR URL)"}]
        }
    ]}
    ]';</copy>
    ```

    - It should look like the following example (Be sure to include the PAR Link inside at of quotes("")):

    ![autopilot set table example](./images/set-table-example.png "autopilot set table example")

4. This command populates all the options needed by Autoload:

    ```bash
    <copy>SET @options = JSON_OBJECT('mode', 'dryrun', 'policy', 'disable_unsupported_columns', 'external_tables', CAST(@dl_tables AS JSON));</copy>
    ```

5. Run this Autoload command:

    ```bash
    <copy>CALL sys.heatwave_load(@db_list, @options);</copy>
    ```

6. Once Autoload completes running, its output has several pieces of information:
    - a. Whether the table exists in the schema you have identified.
    - b. Auto schema inference determines the number of columns in the table.
    - c. Auto schema sampling samples a small number of rows from the table and determines the number of rows in the table and the size of the table.
    - d. Auto provisioning determines how much memory would be needed to load this table into HeatWave and how much time loading this data take.

7. Autoload also generated a statement lke the one below. Execute this statement now.

    ```bash
    <copy>SELECT log->>"$.sql" AS "Load Script" FROM sys.heatwave_autopilot_report WHERE type = "sql" ORDER BY id;</copy>
    ```

    ![Dryrun script](./images/load-script-dryrun.png "load script dryrun")

8. The execution result contains the SQL statements needed to create the table and then load this table data from the Object Store into HeatWave.

    ![Create Table](./images/create-delivery-order.png "create delivery order")

9. Copy the **CREATE TABLE** command from the results. It should look like the following example

    ![autopilot create table with no field name](./images/create-table-no-fieldname.png "autopilot create table with no field name")

10. Execute the **CREATE TABLE** command to create the delivery_orders table.

11. The create command and result should look lie this

    ![Delivery Table create](./images/create-delivery-table.png "create delivery table")

## Task 4: Load complete DELIVERY table from Object Store into MySQL HeatWave

1. Run this command to see the table structure created.

    ```bash
    <copy>desc delivery_orders;</copy>
    ```

    ![Delivery Table structure](./images/describe-delivery-table.png "describe delivery table")

2. Now load the data from the Object Store into the ORDERS table.

    ```bash
    <copy> ALTER TABLE `mysql_customer_orders`.`delivery_orders` SECONDARY_LOAD; </copy>
    ```

3. Check the number of rows loaded into the table.

    ```bash
    <copy>select count(*) from delivery_orders;</copy>
    ```

    The DELIVERY table has 34 million rows.

4. View a sample of the data in the table.

    ```bash
    <copy>select * from delivery_orders limit 5;</copy>
    ```

5. Join the delivery_orders table with other table in the schema

    ```bash
    <copy> select o.* ,d.*  from  orders o
    join delivery_orders d on o.order_id = d.order_id
    where o.order_id = 93751524; </copy>
    ```

6. Output of steps 6 through 5
    ![Add data to table](./images/load-delivery-table.png "load delivery table")

7. Your DELIVERY table is now ready to be used in queries with other tables.

You may now **proceed to the next lab**

## Acknowledgements

- **Author** - Perside Foster, MySQL Solution Engineering

- **Contributors** - Abhinav Agarwal, Senior Principal Product Manager, Nick Mader, MySQL Global Channel Enablement & Strategy Manager
- **Last Updated By/Date** - Perside Foster, MySQL Solution Engineering, May 2023
