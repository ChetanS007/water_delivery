@echo off
set MYSQLDUMP="C:\xampp\mysql\bin\mysqldump.exe"
set OUTFILE="databasefile_2026_05_10.sql"

echo Dumping schema...
%MYSQLDUMP% -u root -p"Chetan@123#" --no-data water_delivery > %OUTFILE%

echo Dumping data...
%MYSQLDUMP% -u root -p"Chetan@123#" --no-create-info --ignore-table=water_delivery.users --ignore-table=water_delivery.daily_deliveries --ignore-table=water_delivery.orders --ignore-table=water_delivery.order_items --ignore-table=water_delivery.cart --ignore-table=water_delivery.customer_payments --ignore-table=water_delivery.payment_history --ignore-table=water_delivery.delivery_assignments --ignore-table=water_delivery.event_bookings --ignore-table=water_delivery.van_logs water_delivery >> %OUTFILE%

echo Dump complete!
