import sys
import json

# Log and return result
try:
    # Get input data
    if len(sys.argv) > 1:
        data = json.loads(sys.argv[1])
        result = {
            "success": True,
            "message": "Order processed successfully",
            "order_id": data.get('order_id'),
            "amount": data.get('amount')
        }
    else:
        result = {
            "success": False,
            "message": "No data received"
        }
        
    # Print result (this will be captured by PHP)
    print(json.dumps(result))
    
    # Exit with success code
    sys.exit(0)
    
except Exception as e:
    error_result = {
        "success": False,
        "message": str(e)
    }
    print(json.dumps(error_result))
    sys.exit(1)